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
use App\Github\Domain\Value\Branch as GithubBranch;
use Github\Exception\ExceptionInterface;
use Gitonomy\Git\Admin;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

use function Symfony\Component\String\u;

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
    private Filesystem $filesystem;
    private Environment $twig;

    public function __construct(
        string $appDir,
        string $githubToken,
        Projects $projects,
        PullRequests $pullRequests,
        Branches $branches,
        Commits $commits,
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
        $this->filesystem = $filesystem;
        $this->twig = $twig;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:files')
            ->setDescription('Dispatches files for all sonata projects.')
            ->addArgument('projects', InputArgument::IS_ARRAY, 'To limit the dispatcher on given project(s).', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projects = $this->projects->all();
        $title = 'Dispatch files for all sonata projects';

        /** @var string[] $projectNames */
        $projectNames = $input->getArgument('projects');
        if ([] !== $projectNames) {
            $projects = $this->projects->byNames($projectNames);
            $title = sprintf(
                'Dispatch files for: %s',
                implode(', ', $projectNames)
            );
        }

        $this->io->title($title);

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

        $gitRepository = Admin::cloneRepository(
            $clonePath,
            sprintf(
                'https://%s:%s@github.com/%s/%s',
                $repository->username(),
                $this->githubToken,
                $repository->username(),
                $repository->name()
            )
        );

        $gitRepository->run('config', ['--local', 'user.name', static::GITHUB_USER]);
        $gitRepository->run('config', ['--local', 'user.email', static::GITHUB_EMAIL]);

        $previousBranch = null;
        $previousDevKitBranchName = null;
        foreach ($project->branchesReverse() as $branch) {
            // We have to fetch all branches on each step in case a PR is submitted.
            $remoteBranchNames = array_map(
                static fn (GithubBranch $branch): string => $branch->name(),
                $this->branches->all($repository)
            );

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
                )['ahead_by'] > 0) {
                    $this->io->comment('The previous branch is not merged into the current one! Do nothing!');

                    continue;
                }
            }

            $gitRepository->run('reset', ['--hard']);

            // Checkout the targeted branch
            if ($gitRepository->getReferences()->hasBranch($branch->name())) {
                $gitRepository->run('checkout', [$branch->name()]);
            } else {
                $gitRepository->run('checkout', ['-b', $branch->name(), '--track', sprintf('origin/%s', $branch->name())]);
            }

            // Checkout the dev-kit branch
            if ($gitRepository->getReferences()->hasRemoteBranch(sprintf('origin/%s', $devKitBranchName))) {
                $gitRepository->run('checkout', ['-b', $devKitBranchName, '--track', sprintf('origin/%s', $devKitBranchName)]);
            } else {
                $gitRepository->run('checkout', ['-b', $devKitBranchName]);
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

            $gitRepository->run('add', ['.', '--all']);

            $diff = $gitRepository->run('diff', ['--color', '--cached', sprintf('origin/%s', $branch->name())]);

            if ('' !== $diff) {
                $this->io->writeln($diff);

                if ($this->apply) {
                    $changes = $gitRepository->run('status', ['-s']);

                    if ('' !== $changes) {
                        $gitRepository->run('commit', ['-m', "'DevKit updates'"]);
                        $gitRepository->run('push', ['-u', 'origin', $devKitBranchName]);
                    }

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

        $filesToRemove = [];

        if (!$project->hasDocumentation()) {
            $filesToRemove[] = $branch->docsPath()->toString();
            $filesToRemove[] = '.github/workflows/documentation.yaml';
            $filesToRemove[] = '.readthedocs.yaml';
        }

        if (!$branch->hasFrontend()) {
            $filesToRemove[] = '.babelrc.js';
            $filesToRemove[] = '.eslintrc.js';
            $filesToRemove[] = '.prettierignore';
            $filesToRemove[] = '.stylelintrc.js';
            $filesToRemove[] = 'postcss.config.js';
            $filesToRemove[] = 'prettier.config.js';
            $filesToRemove[] = '.github/workflows/frontend.yaml';
        }

        if (!$project->isBundle()) {
            $filesToRemove[] = '.symfony.bundle.yaml';
        }

        if (!$project->hasTestKernel()) {
            $filesToRemove[] = 'bin/console';
            $filesToRemove[] = '.github/workflows/symfony-lint.yaml';
        }

        foreach ($filesToRemove as $fileToRemove) {
            $file = u($distPath)
                ->append('/')
                ->append($fileToRemove)
                ->toString();

            if ($this->filesystem->exists($file)) {
                $this->filesystem->remove($file);
            }
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
                static fn (ExcludedFile $excludedFile): string => $excludedFile->filename(),
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
            if (false === $localDirectory) {
                throw new \RuntimeException(sprintf(
                    'Cannot read "%s" dir',
                    $localFullPath
                ));
            }

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

        $distDir = \dirname($distPath);
        if (!$this->filesystem->exists($distDir)) {
            $this->filesystem->mkdir($distDir);
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
                $distDir,
                basename($distPath, '.twig')
            );

            $localContent = $this->twig->render(
                $localPath,
                [
                    'project' => $project,
                    'branch' => $branch,
                    'project_dir' => $distDir,
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
