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

use App\Action\DetermineNextRelease;
use App\Action\Exception\NoPullRequestsMergedSinceLastRelease;
use App\Config\Projects;
use App\Domain\Value\Branch;
use App\Domain\Value\NextRelease;
use App\Domain\Value\Project;
use App\Domain\Value\Stability;
use App\Git\GitManipulator;
use App\Github\Api\PullRequests;
use App\Github\Api\Releases;
use App\Github\Domain\Value\CheckRuns;
use App\Github\Domain\Value\CombinedStatus;
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\PullRequest;
use Gitonomy\Git\Repository;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Webmozart\Assert\Assert;

use function Symfony\Component\String\u;

/**
 * @author Jordi Sala <jordism91@gmail.com>
 */
final class ReleaseCommand extends AbstractCommand
{
    private Projects $projects;
    private DetermineNextRelease $determineNextRelease;
    private GitManipulator $gitManipulator;
    private PullRequests $pullRequests;
    private Releases $releases;

    public function __construct(
        Projects $projects,
        DetermineNextRelease $determineNextRelease,
        GitManipulator $gitManipulator,
        PullRequests $pullRequests,
        Releases $releases
    ) {
        parent::__construct();

        $this->projects = $projects;
        $this->determineNextRelease = $determineNextRelease;
        $this->gitManipulator = $gitManipulator;
        $this->pullRequests = $pullRequests;
        $this->releases = $releases;
    }

    protected function configure(): void
    {
        parent::configure();

        $help = <<<'EOT'
            The <info>release</info> command analyzes pull request of a given project to determine
            the changelog and the next version to release.

            Usage:

            <info>php dev-kit release</info>

            First, a question about what bundle to release will be shown, this will be autocompleted will
            the projects configured on <info>projects.yaml</info>.

            The command will show what is the status of the project, then a list of pull requests
            made against the stable branch with the following information:

             * stability
             * name
             * labels
             * changelog
             * url

            After that, it will show what is the next version to release and the changelog for that release.
            EOT;

        $this
            ->setName('release')
            ->setDescription('Helps with a project release.')
            ->setHelp($help)
            ->addArgument('project', InputArgument::OPTIONAL, 'Name of the project to release', null)
            ->addArgument('branch', InputArgument::OPTIONAL, 'Branch of the project to release', null)
            ->addOption('pr', null, InputOption::VALUE_NONE, 'Create the release PR automatically');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $this->selectProject($input, $output);
        $branch = $this->selectBranch($input, $output, $project);

        $this->io->getErrorStyle()->title($project->name());

        $nextRelease = $this->renderNextRelease($project, $branch);

        if (null !== $nextRelease && $this->createPR($input, $output)) {
            $this->prepareReleasePR($nextRelease);
        }

        return null !== $nextRelease ? 0 : 1;
    }

    private function selectProject(InputInterface $input, OutputInterface $output): Project
    {
        $argumentProject = $input->getArgument('project');

        if (null !== $argumentProject) {
            return $this->projects->byName($argumentProject);
        }

        $helper = $this->getQuestionHelper();

        $question = new Question('<info>Please enter the name of the project to release:</info> ');
        $question->setAutocompleterValues(array_keys($this->projects->all()));
        $question->setTrimmable(true);
        $question->setValidator(fn ($answer): Project => $this->projects->byName($answer));
        $question->setMaxAttempts(3);

        $project = $helper->ask($input, $output, $question);
        Assert::isInstanceOf($project, Project::class);

        return $project;
    }

    private function selectBranch(InputInterface $input, OutputInterface $output, Project $project): Branch
    {
        $argumentBranch = $input->getArgument('branch');

        if (null !== $argumentBranch) {
            return $project->branch($argumentBranch);
        }

        $helper = $this->getQuestionHelper();

        $default = ($project->stableBranch() ?? $project->unstableBranch())->name();

        $question = new ChoiceQuestion(
            sprintf('<info>Please select the branch of the project to release:</info> (Default: "%s")', $default),
            $project->branchNamesReverse(),
            $default
        );
        $question->setTrimmable(true);
        $question->setValidator(static fn ($answer): Branch => $project->branch($answer));
        $question->setMaxAttempts(3);

        $branch = $helper->ask($input, $output, $question);
        Assert::isInstanceOf($branch, Branch::class);

        return $branch;
    }

    private function createPR(InputInterface $input, OutputInterface $output): bool
    {
        if ($input->getOption('pr')) {
            return true;
        }

        $question = new ConfirmationQuestion('Do you want to create a PR to release? (y/N) ', false);

        $helper = $this->getQuestionHelper();
        $doReleasePR = $helper->ask($input, $output, $question);
        Assert::boolean($doReleasePR);

        return $doReleasePR;
    }

    private function renderNextRelease(Project $project, Branch $branch): ?NextRelease
    {
        $notificationStyle = $this->io->getErrorStyle();
        try {
            $nextRelease = $this->determineNextRelease->__invoke($project, $branch);
        } catch (NoPullRequestsMergedSinceLastRelease $e) {
            $notificationStyle->warning($e->getMessage());

            return null;
        }

        if (!$nextRelease->isNeeded()) {
            $notificationStyle->warning('Release is not needed');

            return null;
        }

        $this->renderCombinedStatus($nextRelease->combinedStatus());
        $this->renderCheckRuns($nextRelease->checkRuns());

        $notificationStyle->section('Pull Requests');

        array_map(function (PullRequest $pullRequest): void {
            $this->renderPullRequest($pullRequest);
        }, $nextRelease->pullRequests());

        $notificationStyle->section('Release');

        if (!$nextRelease->canBeReleased()) {
            $notificationStyle->error(sprintf(
                'Next release would be: %s, but cannot be released yet!',
                $nextRelease->nextTag()->toString()
            ));
            $notificationStyle->warning('Please check labels and changelogs of the pull requests!');

            return null;
        }

        $notificationStyle->success(sprintf(
            'Next release will be: %s',
            $nextRelease->nextTag()->toString()
        ));

        // Send markdown to stdout and only that
        $this->io->writeln($nextRelease->changelog()->asMarkdown());

        return $nextRelease;
    }

    private function renderPullRequest(PullRequest $pr): void
    {
        $notificationStyle = $this->io->getErrorStyle();

        $notificationStyle->writeln(sprintf(
            '<info>%s</info>',
            $pr->title()
        ));
        $notificationStyle->writeln($pr->htmlUrl());

        $stability = $pr->stability();
        $notificationStyle->writeln(sprintf(
            '   Stability: %s',
            $stability->equals(Stability::unknown()) ? sprintf('<error>%s</error>', $stability->toUppercaseString()) : $pr->stability()->toUppercaseString()
        ));
        $notificationStyle->writeln(sprintf(
            '      Labels: %s',
            $this->renderLabels($pr)
        ));
        $notificationStyle->writeln(sprintf(
            '   Changelog: %s',
            $pr->fulfilledChangelog() ? '<info>yes</info>' : '<error>no</error>'
        ));

        if ($pr->hasNotNeededChangelog()) {
            $notificationStyle->writeln('<comment>It looks like a changelog is not needed!</comment>');
        }

        $notificationStyle->newLine();
        $notificationStyle->newLine();
    }

    private function renderLabels(PullRequest $pr): string
    {
        if (!$pr->hasLabels()) {
            return '<error>No labels set!</error>';
        }

        $renderedLabels = array_map(
            static fn (Label $label): string => sprintf(
                '<fg=black;bg=%s>%s</>',
                $label->color()->asHexCode(),
                $label->name()
            ),
            $pr->labels()
        );

        return implode(', ', $renderedLabels);
    }

    private function renderCombinedStatus(CombinedStatus $combinedStatus): void
    {
        $notificationStyle = $this->io->getErrorStyle();

        if ([] === $combinedStatus->statuses()) {
            return;
        }

        $table = new Table($notificationStyle);
        $table->setStyle('box');
        $table->setHeaderTitle('Statuses');
        $table->setHeaders([
            'Name',
            'Description',
            'URL',
        ]);

        foreach ($combinedStatus->statuses() as $status) {
            $table->addRow([
                $status->contextFormatted(),
                $status->description(),
                $status->targetUrl(),
            ]);
        }

        $table->render();
    }

    private function renderCheckRuns(CheckRuns $checkRuns): void
    {
        $notificationStyle = $this->io->getErrorStyle();

        if ([] === $checkRuns->all()) {
            return;
        }

        $table = new Table($notificationStyle);
        $table->setStyle('box');
        $table->setHeaderTitle('Checks');
        $table->setHeaders([
            'Name',
            'URL',
        ]);

        foreach ($checkRuns->all() as $checkRun) {
            $table->addRow([
                $checkRun->nameFormatted(),
                $checkRun->detailsUrl(),
            ]);
        }

        $table->render();
    }

    private function prepareReleasePR(NextRelease $nextRelease): void
    {
        $notificationStyle = $this->io->getErrorStyle();
        $notificationStyle->section('Create PR to release');

        $gitRepository = $this->gitManipulator->gitCloneProject($nextRelease->project());

        $devKitBranchName = $this->gitManipulator->prepareBranch($gitRepository, $nextRelease->branch(), '-release-'.$nextRelease->nextTag()->toString());

        $this->updateChangelog($gitRepository, $nextRelease);

        $gitRepository->run('add', ['.', '--all']);
        $diff = $gitRepository->run('diff', ['--color', '--cached', sprintf('origin/%s', $nextRelease->branch()->name())]);

        if ('' !== $diff) {
            $this->io->writeln($diff);

            $changes = $gitRepository->run('status', ['-s']);

            if ('' !== $changes) {
                $gitRepository->run('commit', ['-m', $nextRelease->nextTag()->toString()]);
                $gitRepository->run('push', ['-u', 'origin', $devKitBranchName]);
            }

            $currentHead = u('sonata-project:')->append($devKitBranchName)->toString();

            // If the Pull Request does not exists yet, create it.
            if (!$this->pullRequests->hasOpenPullRequest($nextRelease->project()->repository(), $currentHead)) {
                $this->pullRequests->create(
                    $nextRelease->project()->repository(),
                    sprintf(
                        'Release %s',
                        $nextRelease->nextTag()->toString()
                    ),
                    $currentHead,
                    $nextRelease->branch()->name(),
                    "This PR was created automatically by the `sonata-project/dev-kit` project.\nMake sure to manually replace the `@deprecated` comments with the appropriate version before merging."
                );
                $this->releases->createDraft($nextRelease);
            }
        }
    }

    private function updateChangelog(Repository $gitRepository, NextRelease $nextRelease): void
    {
        $changelogFilePath = $gitRepository->getPath().'/CHANGELOG.md';

        $changelogFileContents = file_get_contents($changelogFilePath);

        if (false === $changelogFileContents) {
            throw new \RuntimeException(sprintf(
                'Cannot read "%s" file',
                $changelogFilePath
            ));
        }

        $replaced = preg_replace('/(This project adheres to \[Semantic Versioning\]\(http:\/\/semver.org\/\)\.)\n/', '$1'.\PHP_EOL.\PHP_EOL.$nextRelease->changelog()->asMarkdown(), $changelogFileContents);

        $res = file_put_contents($changelogFilePath, $replaced);

        if (false === $res) {
            throw new \RuntimeException(sprintf(
                'Cannot write "%s" file',
                $changelogFilePath
            ));
        }
    }

    /**
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType
     */
    private function getQuestionHelper(): QuestionHelper
    {
        return $this->getHelper('question');
    }
}
