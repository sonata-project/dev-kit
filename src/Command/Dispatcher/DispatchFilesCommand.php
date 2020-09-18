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

namespace App\Command\Dispatcher;

use App\Command\AbstractNeedApplyCommand;
use App\Config\Projects;
use App\Domain\Value\Project;
use App\Domain\Value\Repository;
use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use GitWrapper\GitWrapper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use function Symfony\Component\String\u;
use Twig\Environment;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class DispatchFilesCommand extends AbstractNeedApplyCommand
{
    private const FILES_DIR = 'project';

    private string $appDir;
    private string $githubToken;
    private Projects $projects;
    private GithubClient $github;
    private GitWrapper $git;
    private Filesystem $filesystem;
    private Environment $twig;

    public function __construct(string $appDir, string $githubToken, Projects $projects, GithubClient $github, GitWrapper $git, Filesystem $filesystem, Environment $twig)
    {
        parent::__construct();

        $this->appDir = $appDir;
        $this->githubToken = $githubToken;
        $this->projects = $projects;
        $this->github = $github;
        $this->git = $git;
        $this->filesystem = $filesystem;
        $this->twig = $twig;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:files')
            ->setDescription('Dispatches files for all sonata projects.')
            ->addArgument('projects', InputArgument::IS_ARRAY, 'To limit the dispatcher on given project(s).', [])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projects = $this->projects->all();

        $title = 'Dispatch files for all sonata projects';
        if ([] !== $input->getArgument('projects')) {
            $projects = $this->projects->byNames($input->getArgument('projects'));
            $title = sprintf(
                'Dispatch files for: %s',
                implode(', ', $input->getArgument('projects'))
            );
        }

        $this->io->title($title);

        /** @var Project $project */
        foreach ($projects as $project) {
            try {
                $this->io->section($project->name());

                $this->dispatchFiles($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function dispatchFiles(Project $project): void
    {
        $projectConfig = $project->rawConfig();

        $repository = $project->repository();

        // No branch to manage, continue to next project.
        if (!$project->hasBranches()) {
            return;
        }

        // Clone the repository.
        $clonePath = sprintf(
            '%s/%s',
            sys_get_temp_dir(),
            $repository->toString()
        );

        if ($this->filesystem->exists($clonePath)) {
            $this->filesystem->remove($clonePath);
        }

        $git = $this->git->cloneRepository(
            sprintf(
                'https://%s:%s@github.com/%s/%s',
                $repository->vendor(),
                $this->githubToken,
                $repository->vendor(),
                $repository->name()
            ),
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
            }, $this->github->repos()->branches($repository->vendor(), $repository->name()));

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
                $repository->vendor(),
                $repository->name(),
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

            $this->renderFile(
                $project,
                $repository,
                $currentBranch,
                $clonePath
            );

            $this->deleteNotNeededFilesAndDirs(
                $project,
                $currentBranch,
                $clonePath
            );

            $git->add('.', ['all' => true]);
            $diff = $git->diff('--color', '--cached');

            if (!empty($diff)) {
                $this->io->writeln($diff);
                if ($this->apply) {
                    $git->commit('DevKit updates');
                    $git->push('-u', 'origin', $currentDevKit);

                    $currentHead = u('sonata-project:')->append($currentDevKit)->toString();

                    // If the Pull Request does not exists yet, create it.
                    $pulls = $this->github->pullRequests()->all($repository->vendor(), $repository->name(), [
                        'state' => 'open',
                        'head' => $currentHead,
                    ]);

                    if (0 === \count($pulls)) {
                        $this->github->pullRequests()->create($repository->vendor(), $repository->name(), [
                            'title' => sprintf(
                                'DevKit updates for %s branch',
                                $currentBranch
                            ),
                            'head' => $currentHead,
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

    private function deleteNotNeededFilesAndDirs(Project $project, string $branchName, string $distPath, string $localPath = self::FILES_DIR): void
    {
        if (static::FILES_DIR !== $localPath && 0 !== strpos($localPath, static::FILES_DIR.'/')) {
            throw new \LogicException(sprintf(
                'This method only supports files inside the "%s" directory',
                static::FILES_DIR
            ));
        }

        if ($project->docsTarget()) {
            return;
        }

        $projectConfig = $project->rawConfig();

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

    private function renderFile(Project $project, Repository $repository, string $branchName, string $distPath, string $localPath = self::FILES_DIR): void
    {
        $package = $project->package();

        if (static::FILES_DIR !== $localPath && 0 !== strpos($localPath, static::FILES_DIR.'/')) {
            throw new \LogicException(sprintf(
                'This method only supports files inside the "%s" directory',
                static::FILES_DIR
            ));
        }

        $projectConfig = $project->rawConfig();

        if (\in_array(substr($localPath, \strlen(static::FILES_DIR.'/')), $projectConfig['excluded_files'], true)) {
            return;
        }

        $localFullPath = sprintf(
            '%s/templates/%s',
            $this->appDir,
            $localPath
        );

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
                        $project,
                        $repository,
                        $branchName,
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
                $projectConfig,
                $branchConfig,
                [
                    'project' => $project,
                    'package_description' => $package->getDescription(),
                    'packagist_name' => $package->getName(),
                    'is_abandoned' => $package->isAbandoned(),
                    'repository_name' => $repository->name(),
                    'current_branch' => $branchName,
                    'unstable_branch' => $unstableBranch,
                    'stable_branch' => $stableBranch,
                    'website_path' => $project->websitePath(),
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
