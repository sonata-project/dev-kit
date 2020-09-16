<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Util\Util;
use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use GitWrapper\GitWrapper;
use Packagist\Api\Client as PackagistClient;
use Packagist\Api\Result\Package;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;
use Twig\Environment;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class DispatchCommand extends AbstractNeedApplyCommand
{
    private const FILES_DIR = 'project';

    private string $appDir;
    private string $githubToken;
    private PackagistClient $packagist;
    private GithubClient $github;
    private GitWrapper $git;
    private Filesystem $filesystem;
    private Environment $twig;

    /**
     * @var string[]
     */
    private array $projects;

    public function __construct(string $appDir, string $githubToken, PackagistClient $packagist, GithubClient $github, GitWrapper $git, Filesystem $filesystem, Environment $twig)
    {
        parent::__construct();

        $this->appDir = $appDir;
        $this->githubToken = $githubToken;
        $this->packagist = $packagist;
        $this->github = $github;
        $this->git = $git;
        $this->filesystem = $filesystem;
        $this->twig = $twig;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch')
            ->setDescription('Dispatches configuration and documentation files for all sonata projects.')
            ->addArgument('projects', InputArgument::IS_ARRAY, 'To limit the dispatcher on given project(s).', [])
            ->addOption('with-files', null, InputOption::VALUE_NONE, 'Applies Pull Request actions for projects files')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->projects = \count($input->getArgument('projects'))
            ? $input->getArgument('projects')
            : array_keys($this->configs['projects'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $notConfiguredProjects = array_diff($this->projects, array_keys($this->configs['projects']));
        if (\count($notConfiguredProjects)) {
            $this->io->error(sprintf(
                'Some specified projects are not configured: %s ',
                implode(', ', $notConfiguredProjects)
            ));

            return 1;
        }

        foreach ($this->projects as $name) {
            try {
                $package = $this->packagist->get(static::PACKAGIST_GROUP.'/'.$name);
                $projectConfig = $this->configs['projects'][$name];
                $this->io->title($package->getName());
                $this->updateBranchesProtection($package, $projectConfig);

                if ($input->getOption('with-files')) {
                    $this->dispatchFiles($package);
                }
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateBranchesProtection(Package $package, array $projectConfig): void
    {
        $repositoryName = Util::getRepositoryNameWithoutVendorPrefix($package);
        $branches = array_keys($projectConfig['branches']);
        $this->io->section('Branches protection');

        foreach ($branches as $branch) {
            $requiredStatusChecks = $this->buildRequiredStatusChecks(
                $branch,
                $projectConfig['branches'][$branch],
                $projectConfig['docs_target']
            );

            if ($this->apply) {
                $this->github->repo()->protection()
                    ->update(static::GITHUB_GROUP, $repositoryName, $branch, [
                        'required_status_checks' => [
                            'strict' => false,
                            'contexts' => $requiredStatusChecks,
                        ],
                        'required_pull_request_reviews' => [
                            'dismissal_restrictions' => [
                                'users' => [],
                                'teams' => [],
                            ],
                            'dismiss_stale_reviews' => true,
                            'require_code_owner_reviews' => true,
                        ],
                        'restrictions' => null,
                        'enforce_admins' => false,
                    ]);
            }
        }

        if ($this->apply) {
            $this->io->comment('Branches protection applied.');
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    private function buildRequiredStatusChecks(string $branchName, array $branchConfig, bool $docsTarget): array
    {
        $targetPhp = $branchConfig['target_php'] ?? end($branchConfig['php']);
        $requiredStatusChecks = [
            'composer-normalize',
            'YAML files',
            'XML files',
            'PHP-CS-Fixer',
            sprintf('PHP %s + lowest + normal', reset($branchConfig['php'])),
        ];

        if ($docsTarget) {
            $requiredStatusChecks[] = 'Sphinx build';
            $requiredStatusChecks[] = 'DOCtor-RST';
        }

        foreach ($branchConfig['php'] as $phpVersion) {
            $requiredStatusChecks[] = sprintf('PHP %s + highest + normal', $phpVersion);
        }

        foreach ($branchConfig['variants'] as $variant => $versions) {
            foreach ($versions as $version) {
                $requiredStatusChecks[] = sprintf(
                    'PHP %s + highest + %s:"%s"',
                    $targetPhp,
                    $variant,
                    'dev-master' === $version ? $version : ($version.'.*'),
                );
            }
        }

        $this->io->writeln(sprintf(
            'Required Status-Checks for <info>%s</info>:',
            $branchName
        ));
        $this->io->listing($requiredStatusChecks);

        return $requiredStatusChecks;
    }

    private function dispatchFiles(Package $package): void
    {
        $repositoryName = Util::getRepositoryNameWithoutVendorPrefix($package);
        $projectConfig = $this->configs['projects'][str_replace(static::PACKAGIST_GROUP.'/', '', $package->getName())];

        // No branch to manage, continue to next project.
        if (0 === \count($projectConfig['branches'])) {
            return;
        }

        // Clone the repository.
        $clonePath = sys_get_temp_dir().'/sonata-project/'.$repositoryName;
        if ($this->filesystem->exists($clonePath)) {
            $this->filesystem->remove($clonePath);
        }

        $git = $this->git->cloneRepository(
            'https://'.static::GITHUB_USER.':'.$this->githubToken.'@github.com/'.static::GITHUB_GROUP.'/'.$repositoryName,
            $clonePath
        );

        $git->config('user.name', static::GITHUB_USER);
        $git->config('user.email', static::GITHUB_EMAIL);

        $branches = array_reverse($projectConfig['branches']);

        $previousBranch = null;
        $previousDevKit = null;
        while (($branchConfig = current($branches))) {
            // We have to fetch all branches on each step in case a PR is submitted.
            $remoteBranches = array_map(static function ($branch) {
                return $branch['name'];
            }, $this->github->repos()->branches(static::GITHUB_GROUP, $repositoryName));

            $currentBranch = key($branches);
            $currentDevKit = u($currentBranch)->append('-dev-kit')->toString();
            next($branches);

            // A PR is already here for previous branch, do nothing on the current one.
            if (\in_array($previousDevKit, $remoteBranches, true)) {
                continue;
            }

            // Diff application
            $this->io->section('Files for '.$currentBranch);

            // If the previous branch is not merged into the current one, do nothing.
            if ($previousBranch && $this->github->repos()->commits()->compare(
                static::GITHUB_GROUP,
                $repositoryName,
                $currentBranch,
                $previousBranch
            )['ahead_by']) {
                $this->io->comment('The previous branch is not merged into the current one! Do nothing!');

                continue;
            }

            $git->reset(['hard' => true]);

            // Checkout the targeted branch
            if (\in_array($currentBranch, $git->getBranches()->all(), true)) {
                $git->checkout($currentBranch);
            } else {
                $git->checkout('-b', $currentBranch, '--track', 'origin/'.$currentBranch);
            }
            // Checkout the dev-kit branch
            if (\in_array('remotes/origin/'.$currentDevKit, $git->getBranches()->all(), true)) {
                $git->checkout('-b', $currentDevKit, '--track', 'origin/'.$currentDevKit);
            } else {
                $git->checkout('-b', $currentDevKit);
            }

            $this->renderFile($package, $repositoryName, $currentBranch, $projectConfig, $clonePath);
            $this->deleteNotNeededFilesAndDirs($currentBranch, $projectConfig, $clonePath);

            $git->add('.', ['all' => true]);
            $diff = $git->diff('--color', '--cached');

            if (!empty($diff)) {
                $this->io->writeln($diff);
                if ($this->apply) {
                    $git->commit('DevKit updates');
                    $git->push('-u', 'origin', $currentDevKit);

                    // If the Pull Request does not exists yet, create it.
                    $pulls = $this->github->pullRequests()->all(static::GITHUB_GROUP, $repositoryName, [
                        'state' => 'open',
                        'head' => 'sonata-project:'.$currentDevKit,
                    ]);

                    if (0 === \count($pulls)) {
                        $this->github->pullRequests()->create(static::GITHUB_GROUP, $repositoryName, [
                            'title' => 'DevKit updates for '.$currentBranch.' branch',
                            'head' => 'sonata-project:'.$currentDevKit,
                            'base' => $currentBranch,
                            'body' => '',
                        ]);
                    }

                    // Wait 200ms to be sure GitHub API is up to date with new pushed branch/PR.
                    usleep(200000);
                }
            } else {
                $this->io->comment(static::LABEL_NOTHING_CHANGED);
            }

            // Save the current branch to the previous and go to next step
            $previousBranch = $currentBranch;
            $previousDevKit = $currentDevKit;
        }
    }

    private function deleteNotNeededFilesAndDirs(string $branchName, array $projectConfig, string $distPath, string $localPath = self::FILES_DIR): void
    {
        if (static::FILES_DIR !== $localPath && 0 !== strpos($localPath, static::FILES_DIR.'/')) {
            throw new \LogicException(sprintf(
                'This method only supports files inside the "%s" directory',
                static::FILES_DIR
            ));
        }

        if ($projectConfig['docs_target']) {
            return;
        }

        $docsPath = $projectConfig['branches'][$branchName]['docs_path'];

        $docsDirectory = u($distPath)
            ->append('/')
            ->append($docsPath)
            ->toString();

        $this->io->writeln(sprintf(
            'Delete <info>/%s</info> directory!',
            $docsPath
        ));
        $this->filesystem->remove($docsDirectory);

        $filepath = '.github/workflows/documentation.yaml';
        $documentationWorkflowFile = u($distPath)
            ->append('/')
            ->append($filepath)
            ->toString();

        $this->io->writeln(sprintf(
            'Delete <info>/%s</info> file!',
            $filepath
        ));
        $this->filesystem->remove($documentationWorkflowFile);
    }

    private function renderFile(Package $package, string $repositoryName, string $branchName, array $projectConfig, string $distPath, string $localPath = self::FILES_DIR): void
    {
        if (static::FILES_DIR !== $localPath && 0 !== strpos($localPath, static::FILES_DIR.'/')) {
            throw new \LogicException(sprintf(
                'This method only supports files inside the "%s" directory',
                static::FILES_DIR
            ));
        }

        if (\in_array(substr($localPath, \strlen(static::FILES_DIR.'/')), $projectConfig['excluded_files'], true)) {
            return;
        }

        $localFullPath = $this->appDir.'/templates/'.$localPath;

        $localFileType = filetype($localFullPath);
        if (false === $localFileType) {
            throw new \RuntimeException(sprintf(
                'Cannot get "%s" file type',
                $localFullPath
            ));
        }

        $distFileType = $this->filesystem->exists($distPath) ? filetype($distPath) : false;
        if ($localFileType !== $distFileType && false !== $distFileType) {
            throw new \LogicException(sprintf(
                'File type mismatch between "%s" and "%s"',
                $localPath,
                $distPath
            ));
        }

        if ('dir' === $localFileType) {
            $localDirectory = dir($localFullPath);
            while (false !== ($entry = $localDirectory->read())) {
                if (!\in_array($entry, ['.', '..'], true)) {
                    $this->renderFile(
                        $package,
                        $repositoryName,
                        $branchName,
                        $projectConfig,
                        $distPath.'/'.$entry,
                        $localPath.'/'.$entry,
                    );
                }
            }

            return;
        }

        $localContent = file_get_contents($localFullPath);
        if (false === $localContent) {
            throw new \RuntimeException(sprintf(
                'Cannot read "%s" file content',
                $localFullPath
            ));
        }

        if (!$this->filesystem->exists(\dirname($distPath))) {
            $this->filesystem->mkdir(\dirname($distPath));
        }

        $branchConfig = $projectConfig['branches'][$branchName];
        $localPathInfo = pathinfo($localFullPath);

        if (u($localPathInfo['basename'])->startsWith('DELETE_')) {
            $fileToDelete = u($distPath)->replace('DELETE_', '')->toString();

            if ($this->filesystem->exists($fileToDelete)) {
                $this->filesystem->remove($fileToDelete);
            }

            return;
        }

        if (\array_key_exists('extension', $localPathInfo) && 'twig' === $localPathInfo['extension']) {
            $distPath = \dirname($distPath).'/'.basename($distPath, '.twig');

            reset($projectConfig['branches']);
            $unstableBranch = key($projectConfig['branches']);
            $stableBranch = next($projectConfig['branches']) ? key($projectConfig['branches']) : $unstableBranch;

            $res = file_put_contents($distPath, $this->twig->render($localPath, array_merge(
                $this->configs,
                $projectConfig,
                $branchConfig,
                [
                    'package_title' => ucwords(str_replace(['-project', '/', '-'], ['', ' ', ' '], $package->getName())),
                    'package_description' => $package->getDescription(),
                    'packagist_name' => $package->getName(),
                    'is_abandoned' => $package->isAbandoned(),
                    'repository_name' => $repositoryName,
                    'current_branch' => $branchName,
                    'unstable_branch' => $unstableBranch,
                    'stable_branch' => $stableBranch,
                    'website_path' => str_replace([static::PACKAGIST_GROUP.'/', '-bundle'], '', $package->getName()),
                ]
            )));
        } else {
            $res = file_put_contents($distPath, $localContent);
        }

        if (false === $res) {
            throw new \RuntimeException(sprintf(
                'Cannot write "%s" file',
                $distPath
            ));
        }

        $localPerms = fileperms($localFullPath);
        if (false === $localPerms) {
            throw new \RuntimeException(sprintf(
                'Cannot read "%s" file perms',
                $localFullPath
            ));
        }

        // Restore file permissions after content copy
        $this->filesystem->chmod($distPath, $localPerms);
    }
}
