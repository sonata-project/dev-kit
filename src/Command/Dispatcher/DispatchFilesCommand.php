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
use App\Domain\Value\Branch;
use App\Domain\Value\ExcludedFile;
use App\Domain\Value\Project;
use App\Domain\Value\Repository;
use App\Github\Api\Branches;
use App\Github\Api\Commits;
use App\Github\Api\PullRequests;
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
    private PullRequests $pullRequests;
    private Branches $branches;
    private Commits $commits;
    private GitWrapper $git;
    private Filesystem $filesystem;
    private Environment $twig;

    public function __construct(
        string $appDir,
        string $githubToken,
        Projects $projects,
        PullRequests $pullRequests,
        Branches $branches,
        Commits $commits,
        GitWrapper $git,
        Filesystem $filesystem,
        Environment $twig
    ) {
        parent::__construct();

        $this->appDir = $appDir;
        $this->githubToken = $githubToken;
        $this->projects = $projects;
        $this->pullRequests = $pullRequests;
        $this->branches = $branches;
        $this->commits = $commits;
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
                $repository->username(),
                $this->githubToken,
                $repository->username(),
                $repository->name()
            ),
            $clonePath
        );

        $git->config('user.name', static::GITHUB_USER);
        $git->config('user.email', static::GITHUB_EMAIL);

        $previousBranch = null;
        $previousDevKitBranchName = null;
        foreach ($project->branchesReverse() as $branch) {
            // We have to fetch all branches on each step in case a PR is submitted.
            $remoteBranchNames = array_map(static function (\App\Github\Domain\Value\Branch $branch) {
                return $branch->name();
            }, $this->branches->all($repository));

            $devKitBranchName = u($branch->name())->append('-dev-kit')->toString();

            // A PR is already here for previous branch, do nothing on the current one.
            if (\in_array($previousDevKitBranchName, $remoteBranchNames, true)) {
                continue;
            }

            // Diff application
            $this->io->section(sprintf(
                'Files for %s',
                $branch->name()
            ));

            // If the previous branch is not merged into the current one, do nothing.
            if ($previousBranch instanceof Branch) {
                if ($this->commits->compare(
                    $repository,
                    $branch,
                    $previousBranch
                )['ahead_by']) {
                    $this->io->comment('The previous branch is not merged into the current one! Do nothing!');

                    continue;
                }
            }

            $git->reset(['hard' => true]);

            // Checkout the targeted branch
            if (\in_array($branch->name(), $git->getBranches()->all(), true)) {
                $git->checkout($branch->name());
            } else {
                $git->checkout('-b', $branch->name(), '--track', 'origin/'.$branch->name());
            }

            // Checkout the dev-kit branch
            if (\in_array('remotes/origin/'.$devKitBranchName, $git->getBranches()->all(), true)) {
                $git->checkout('-b', $devKitBranchName, '--track', 'origin/'.$devKitBranchName);
            } else {
                $git->checkout('-b', $devKitBranchName);
            }

            $this->renderFile(
                $project,
                $repository,
                $branch,
                $clonePath
            );

            $this->deleteNotNeededFilesAndDirs(
                $project,
                $branch,
                $clonePath
            );

            $git->add('.', ['all' => true]);
            $diff = $git->diff('--color', '--cached');

            if (!empty($diff)) {
                $this->io->writeln($diff);
                if ($this->apply) {
                    $git->commit('DevKit updates');
                    $git->push('-u', 'origin', $devKitBranchName);

                    $currentHead = u('sonata-project:')->append($devKitBranchName)->toString();

                    // If the Pull Request does not exists yet, create it.
                    if (!$this->pullRequests->hasOpenPullRequest($repository, $currentHead)) {
                        $this->pullRequests->create(
                            $repository,
                            sprintf(
                                'DevKit updates for %s branch',
                                $branch->name()
                            ),
                            $currentHead,
                            $branch->name()
                        );
                    }

                    // Wait 200ms to be sure GitHub API is up to date with new pushed branch/PR.
                    usleep(200000);
                }
            } else {
                $this->io->comment(static::LABEL_NOTHING_CHANGED);
            }

            // Save the current branch to the previous and go to next step
            $previousBranch = $branch;
            $previousDevKitBranchName = $devKitBranchName;
        }
    }

    private function deleteNotNeededFilesAndDirs(Project $project, Branch $branch, string $distPath, string $localPath = self::FILES_DIR): void
    {
        if (static::FILES_DIR !== $localPath && 0 !== strpos($localPath, static::FILES_DIR.'/')) {
            throw new \LogicException(sprintf(
                'This method only supports files inside the "%s" directory',
                static::FILES_DIR
            ));
        }

        if (!$project->hasDocumentation()) {
            $docsPath = $branch->docsPath()->toString();

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

        if (!$project->usesPHPStan() && !$project->usesPsalm()) {
            $filepath = '.github/workflows/qa.yaml';
            $qaWorkflowFile = u($distPath)
                ->append('/')
                ->append($filepath)
                ->toString();

            $this->io->writeln(sprintf(
                'Delete <info>/%s</info> file!',
                $filepath
            ));
            $this->filesystem->remove($qaWorkflowFile);
        }
    }

    private function renderFile(Project $project, Repository $repository, Branch $branch, string $distPath, string $localPath = self::FILES_DIR): void
    {
        if (static::FILES_DIR !== $localPath) {
            if (0 !== strpos($localPath, static::FILES_DIR.'/')) {
                throw new \LogicException(sprintf(
                    'This method only supports files inside the "%s" directory',
                    static::FILES_DIR
                ));
            }

            $excludedFiles = array_map(
                static function (ExcludedFile $excludedFile): string {
                    return $excludedFile->filename();
                },
                $project->excludedFiles()
            );

            $file = substr($localPath, \strlen(static::FILES_DIR.'/'));
            if ('.twig' === substr($file, -5)) {
                $file = substr($file, 0, -5);
            }

            if (\in_array($file, $excludedFiles, true)) {
                return;
            }
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
                        $branch,
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

        $localPathInfo = pathinfo($localFullPath);

        if (u($localPathInfo['basename'])->startsWith('DELETE_')) {
            $fileToDelete = u($distPath)->replace('DELETE_', '')->toString();

            if ($this->filesystem->exists($fileToDelete)) {
                $this->filesystem->remove($fileToDelete);
            }

            return;
        }

        if (\array_key_exists('extension', $localPathInfo) && 'twig' === $localPathInfo['extension']) {
            $distPath = sprintf(
                '%s/%s',
                \dirname($distPath),
                basename($distPath, '.twig')
            );

            $localContent = $this->twig->render(
                $localPath,
                [
                    'project' => $project,
                    'branch' => $branch,
                ]
            );
        }

        $res = file_put_contents($distPath, $localContent);

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
