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
use App\Action\Exception\CannotDetermineNextRelease;
use App\Action\Exception\NoPullRequestsMergedSinceLastRelease;
use App\Config\Projects;
use App\Domain\Value\Project;
use App\Domain\Value\Stability;
use App\Github\Domain\Value\CheckRun;
use App\Github\Domain\Value\CheckRuns;
use App\Github\Domain\Value\CombinedStatus;
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\PullRequest;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Jordi Sala <jordism91@gmail.com>
 */
final class ReleaseCommand extends AbstractCommand
{
    private Projects $projects;
    private DetermineNextRelease $determineNextRelease;

    public function __construct(
        Projects $projects,
        DetermineNextRelease $determineNextRelease
    ) {
        parent::__construct();

        $this->projects = $projects;
        $this->determineNextRelease = $determineNextRelease;
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
            ->setHelp($help);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $project = $this->selectProject($input, $output);

        $this->io->getErrorStyle()->title($project->name());

        return $this->renderNextRelease($project);
    }

    private function selectProject(InputInterface $input, OutputInterface $output): Project
    {
        $helper = $this->getHelper('question');

        $question = new Question('<info>Please enter the name of the project to release:</info> ');
        $question->setAutocompleterValues(array_keys($this->projects->all()));
        $question->setNormalizer(static function ($answer) {
            return $answer ? trim($answer) : '';
        });
        $question->setValidator(function ($answer): Project {
            return $this->projects->byName($answer);
        });
        $question->setMaxAttempts(3);

        return $helper->ask($input, $output, $question);
    }

    private function renderNextRelease(Project $project): int
    {
        $notificationStyle = $this->io->getErrorStyle();
        try {
            $nextRelease = $this->determineNextRelease->__invoke($project);
        } catch (NoPullRequestsMergedSinceLastRelease $e) {
            $notificationStyle->warning($e->getMessage());

            return 0;
        } catch (CannotDetermineNextRelease $e) {
            $notificationStyle->error($e->getMessage());

            return 1;
        }

        if (!$nextRelease->isNeeded()) {
            $notificationStyle->warning('Release is not needed');

            return 0;
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

            return 1;
        }

        $notificationStyle->success(sprintf(
            'Next release will be: %s',
            $nextRelease->nextTag()->toString()
        ));

        $notificationStyle->section('Changelog as Markdown');

        // Send markdown to stdout and only that
        $this->io->writeln($nextRelease->changelog()->asMarkdown());

        return 0;
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

        $renderedLabels = array_map(static function (Label $label): string {
            return sprintf(
                '<fg=black;bg=%s>%s</>',
                $label->color()->asHexCode(),
                $label->name()
            );
        }, $pr->labels());

        return implode(', ', $renderedLabels);
    }

    private function renderCombinedStatus(CombinedStatus $combinedStatus): void
    {
        $notificationStyle = $this->io->getErrorStyle();

        if ($combinedStatus->statuses() === []) {
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

        if ($checkRuns->all() === []) {
            return;
        }

        $table = new Table($notificationStyle);
        $table->setStyle('box');
        $table->setHeaderTitle('Checks');
        $table->setHeaders([
            'Name',
            'URL',
        ]);

        /** @var CheckRun $checkRun */
        foreach ($checkRuns->all() as $checkRun) {
            $table->addRow([
                $checkRun->nameFormatted(),
                $checkRun->detailsUrl(),
            ]);
        }

        $table->render();
    }
}
