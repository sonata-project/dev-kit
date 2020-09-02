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

use App\Domain\Value\Branch;
use App\Domain\Value\Project;
use App\Domain\Value\Repository;
use Github\Exception\ExceptionInterface;
use GitWrapper\GitWrapper;
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
    private const LABEL_NOTHING_CHANGED = 'Nothing to be changed.';
    private const FILES_DIR = 'project';

    /** @var string[] */
    private const HOOK_URLS_TO_BE_DELETED = [
        'https://api.codacy.com',
        'https://www.flowdock.com',
        'http://scrutinizer-ci.com',
        'http://localhost:8000',
        'https://notify.travis-ci.org',
    ];

    /**
     * @var string
     */
    private $appDir;

    /**
     * @var GitWrapper
     */
    private $gitWrapper;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var array<string, Project>
     */
    private $projects = [];

    public function __construct(string $appDir, GitWrapper $gitWrapper, Filesystem $fileSystem, Environment $twig)
    {
        parent::__construct();

        $this->appDir = $appDir;
        $this->gitWrapper = $gitWrapper;
        $this->fileSystem = $fileSystem;
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

        $selectedProjects = $input->getArgument('projects');

        foreach ($this->configs['projects'] as $name => $config) {
            if ($selectedProjects > 0
                && !\in_array($name, $selectedProjects, true)
            ) {
                continue;
            }

            $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);

            $this->projects[$name] = Project::fromValues($name, $config, $package);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Project $project */
        foreach ($this->projects as $project) {
            try {
                $this->io->title($project->name());

                $repository = $project->repository();

                $this->updateRepositories($project);
                $this->deleteHooks($repository);
                $this->updateDevKitHook($repository);
                $this->updateLabels($repository);
                $this->updateBranchesProtection($project);

                if ($input->getOption('with-files')) {
                    $this->dispatchFiles($project);
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

    /**
     * Sets repository information and general settings.
     */
    private function updateRepositories(Project $project): void
    {
        $repository = $project->repository();
        $branches = $project->branches();
        $defaultBranch = end($branches);

        $this->io->section('Repository');

        $repositoryInfo = $this->githubClient->repo()->show(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix());
        $infoToUpdate = [
            'homepage' => 'https://sonata-project.org/',
            'has_issues' => true,
            'has_projects' => true,
            'has_wiki' => false,
            'default_branch' => $defaultBranch,
            'allow_squash_merge' => true,
            'allow_merge_commit' => false,
            'allow_rebase_merge' => true,
        ];

        foreach ($infoToUpdate as $info => $value) {
            if ($value === $repositoryInfo[$info]) {
                unset($infoToUpdate[$info]);
            }
        }

        if (\count($infoToUpdate)) {
            $this->io->comment(sprintf(
                'Following info have to be changed: %s.',
                implode(', ', array_keys($infoToUpdate))
            ));

            if ($this->apply) {
                $this->githubClient->repo()->update(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), array_merge($infoToUpdate, [
                    'name' => $repository->nameWithoutVendorPrefix(),
                ]));
            }
        } elseif (!\count($infoToUpdate)) {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    private function updateLabels(Repository $repository): void
    {
        $this->io->section('Labels');

        $configuredLabels = $this->configs['labels'];
        $missingLabels = $configuredLabels;

        $headers = [
            'Name',
            'Actual color',
            'Needed color',
            'State',
        ];

        $rows = [];

        foreach ($this->githubClient->repo()->labels()->all(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix()) as $label) {
            $name = $label['name'];
            $color = $label['color'];

            $shouldExist = \array_key_exists($name, $configuredLabels);
            $configuredColor = $shouldExist ? $configuredLabels[$name]['color'] : null;
            $shouldBeUpdated = $shouldExist && $color !== $configuredColor;

            if ($shouldExist) {
                unset($missingLabels[$name]);
            }

            $state = null;
            if (!$shouldExist) {
                $state = 'Deleted';
                if ($this->apply) {
                    $this->githubClient->repo()->labels()->remove(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $name);
                }
            } elseif ($shouldBeUpdated) {
                $state = 'Updated';
                if ($this->apply) {
                    $this->githubClient->repo()->labels()->update(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $name, [
                        'name' => $name,
                        'color' => $configuredColor,
                    ]);
                }
            }

            if ($state) {
                array_push($rows, [
                    $name,
                    '#'.$color,
                    $configuredColor ? '#'.$configuredColor : 'N/A',
                    $state,
                ]);
            }
        }

        foreach ($missingLabels as $name => $label) {
            $color = $label['color'];

            if ($this->apply) {
                $this->githubClient->repo()->labels()->create(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), [
                    'name' => $name,
                    'color' => $color,
                ]);
            }
            array_push($rows, [$name, 'N/A', '#'.$color, 'Created']);
        }

        usort($rows, static function ($row1, $row2): int {
            return strcasecmp($row1[0], $row2[0]);
        });

        if (empty($rows)) {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        } else {
            $this->io->table($headers, $rows);

            if ($this->apply) {
                $this->io->success('Labels successfully updated.');
            }
        }
    }

    private function updateDevKitHook(Repository $repository): void
    {
        $this->io->section('DevKit hook');

        // Construct the hook url.
        $hookToken = getenv('DEK_KIT_TOKEN') ? getenv('DEK_KIT_TOKEN') : 'INVALID_TOKEN';
        $hookBaseUrl = 'https://d5zda2diva-x6miu6vkqhzpi.eu.s5y.io/github';
        $hookCompleteUrl = sprintf(
            '%s?%s',
            $hookBaseUrl,
            http_build_query([
                'token' => $hookToken,
            ])
        );

        // Set hook configs
        $config = [
            'url' => $hookCompleteUrl,
            'insecure_ssl' => '0',
            'content_type' => 'json',
        ];
        $events = [
            'issue_comment',
            'pull_request',
            'pull_request_review_comment',
        ];

        $configuredHooks = $this->githubClient->repo()->hooks()->all(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix());

        dd($configuredHooks);

        // First, check if the hook exists.
        $devKitHook = null;
        foreach ($configuredHooks as $hook) {
            if (\array_key_exists('url', $hook['config'])
                && 0 === strncmp($hook['config']['url'], $hookBaseUrl, \strlen($hookBaseUrl))) {
                $devKitHook = $hook;

                break;
            }
        }

        if (!$devKitHook) {
            $this->io->comment('Has to be created.');

            if ($this->apply) {
                $this->githubClient->repo()->hooks()->create(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), [
                    'name' => 'web',
                    'config' => $config,
                    'events' => $events,
                    'active' => true,
                ]);
                $this->io->success('Hook created.');
            }
        } elseif (\count(array_diff_assoc($devKitHook['config'], $config))
            || \count(array_diff($devKitHook['events'], $events))
            || !$devKitHook['active']
        ) {
            $this->io->comment('Has to be updated.');

            if ($this->apply) {
                $this->githubClient->repo()->hooks()->update(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $devKitHook['id'], [
                    'name' => 'web',
                    'config' => $config,
                    'events' => $events,
                    'active' => true,
                ]);
                $this->githubClient->repo()->hooks()->ping(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $devKitHook['id']);
                $this->io->success('Hook updated.');
            }
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    private function deleteHooks(Repository $repository): void
    {
        $this->io->section('Check Hooks to be deleted');

        $configuredHooks = $this->githubClient->repo()->hooks()->all(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix());

        // Check if hook should be deleted.
        foreach ($configuredHooks as $key => $hook) {
            foreach (self::HOOK_URLS_TO_BE_DELETED as $url) {
                $currentHookUrl = $hook['config']['url'];

                if (u($currentHookUrl)->startsWith($url)) {
                    $this->io->comment(sprintf(
                        'Hook "%s" will be deleted',
                        $currentHookUrl
                    ));

                    if ($this->apply) {
                        $this->githubClient->repo()->hooks()->remove(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $hook['id']);

                        $this->io->success(sprintf(
                            'Hook "%s" deleted.',
                            $currentHookUrl
                        ));
                    }
                }
            }
        }
    }

    private function updateBranchesProtection(Project $project): void
    {
        $repository = $project->repository();

        $this->io->section('Branches protection');

        /** @var Branch $branch */
        foreach ($project->branches() as $branch) {
            $requiredStatusChecks = $this->buildRequiredStatusChecks(
                $branch,
                $project->docsTarget()
            );

            if ($this->apply) {
                $this->githubClient->repo()->protection()
                    ->update(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), $branch, [
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

    private function buildRequiredStatusChecks(Branch $branch, bool $docsTarget): array
    {
        $phpVersions = $branch->phpVersions();
        $requiredStatusChecks = [
            'composer-normalize',
            'YAML files',
            'XML files',
            'PHP-CS-Fixer',
            sprintf('PHP %s + lowest + normal', reset($phpVersions)),
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
                    $branch->targetPhpVersion(),
                    $this->configs['packages'][$variant],
                    'dev-master' === $version ? $version : ($version.'.*'),
                );
            }
        }

        $this->io->writeln(sprintf(
            'Required Status-Checks for <info>%s</info>:',
            $branch->name()
        ));
        $this->io->listing($requiredStatusChecks);

        return $requiredStatusChecks;
    }

    private function dispatchFiles(Project $project): void
    {
        $package = $project->package();
        $repository = $project->repository();

        $projectConfig = $this->configs['projects'][str_replace(static::PACKAGIST_GROUP.'/', '', $package->getName())];

        // No branch to manage, continue to next project.
        if (!$project->hasBranches()) {
            return;
        }

        // Clone the repository.
        $clonePath = sys_get_temp_dir().'/sonata-project/'.$repository->nameWithoutVendorPrefix();
        if ($this->fileSystem->exists($clonePath)) {
            $this->fileSystem->remove($clonePath);
        }

        $git = $this->gitWrapper->cloneRepository(
            'https://'.static::GITHUB_USER.':'.$this->githubAuthKey.'@github.com/'.static::GITHUB_GROUP.'/'.$repository->nameWithoutVendorPrefix(),
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
            }, $this->githubClient->repos()->branches(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix()));

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
            if ($previousBranch && $this->githubClient->repos()->commits()->compare(
                static::GITHUB_GROUP,
                $repository->nameWithoutVendorPrefix(),
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

            $this->renderFile($package, $repository->nameWithoutVendorPrefix(), $currentBranch, $projectConfig, $clonePath);
            $this->deleteNotNeededFilesAndDirs($currentBranch, $projectConfig, $clonePath);

            $git->add('.', ['all' => true]);
            $diff = $git->diff('--color', '--cached');

            if (!empty($diff)) {
                $this->io->writeln($diff);
                if ($this->apply) {
                    $git->commit('DevKit updates');
                    $git->push('-u', 'origin', $currentDevKit);

                    // If the Pull Request does not exists yet, create it.
                    $pulls = $this->githubClient->pullRequests()->all(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), [
                        'state' => 'open',
                        'head' => 'sonata-project:'.$currentDevKit,
                    ]);

                    if (0 === \count($pulls)) {
                        $this->githubClient->pullRequests()->create(static::GITHUB_GROUP, $repository->nameWithoutVendorPrefix(), [
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
        $this->fileSystem->remove($docsDirectory);

        $filepath = '.github/workflows/documentation.yaml';
        $documentationWorkflowFile = u($distPath)
            ->append('/')
            ->append($filepath)
            ->toString();

        $this->io->writeln(sprintf(
            'Delete <info>/%s</info> file!',
            $filepath
        ));
        $this->fileSystem->remove($documentationWorkflowFile);
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

        $distFileType = $this->fileSystem->exists($distPath) ? filetype($distPath) : false;
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

        if (!$this->fileSystem->exists(\dirname($distPath))) {
            $this->fileSystem->mkdir(\dirname($distPath));
        }

        $branchConfig = $projectConfig['branches'][$branchName];
        $localPathInfo = pathinfo($localFullPath);

        if (u($localPathInfo['basename'])->startsWith('DELETE_')) {
            $fileToDelete = u($distPath)->replace('DELETE_', '')->toString();

            if ($this->fileSystem->exists($fileToDelete)) {
                $this->fileSystem->remove($fileToDelete);
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
        $this->fileSystem->chmod($distPath, $localPerms);
    }
}
