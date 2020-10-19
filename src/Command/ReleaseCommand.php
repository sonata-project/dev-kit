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
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\PullRequest;
use App\Github\Domain\Value\Status;
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

        $notificationStyle->section('Checks');

        array_map(function (Status $status): void {
            $this->renderStatus($status);
        }, $nextRelease->combinedStatus()->statuses());

        $notificationStyle->section('Pull Requests');

        array_map(function (PullRequest $pullRequest): void {
            $this->renderPullRequest($pullRequest);
        }, $nextRelease->pullRequests());

        $notificationStyle->section('Release');

        $notificationStyle->success(sprintf(
            'Next release will be: %s',
            $nextRelease->nextTag()->toString()
        ));

        $notificationStyle->section('Changelog');

        // Send markdown to stdout and only that
        $this->io->writeln($nextRelease->changelog()->asMarkdown());

        return 0;
    }

    private function renderPullRequest(PullRequest $pr): void
    {
        $notificationStyle = $this->io->getErrorStyle();

        $this->renderStability($pr->stability());

        $notificationStyle->write(sprintf(
            '<info>%s</info>',
            $pr->title()
        ));

        array_map(function (Label $label): void {
            $this->renderLabel($label);
        }, $pr->labels());

        if (!$pr->hasLabels()) {
            $notificationStyle->write(' <fg=black;bg=yellow>[No labels]</>');
        }

        if (!$pr->hasChangelog() && $pr->stability()->notEquals(Stability::pedantic())) {
            $notificationStyle->write(' <error>[Changelog not found]</error>');
        } elseif (!$pr->hasChangelog()) {
            $notificationStyle->write(' <fg=black;bg=green>[Changelog not found]</>');
        } elseif ($pr->hasChangelog() && $pr->stability()->equals(Stability::pedantic())) {
            $notificationStyle->write(' <fg=black;bg=yellow>[Changelog found]</>');
        } else {
            $notificationStyle->write(' <fg=black;bg=green>[Changelog found]</>');
        }

        $notificationStyle->newLine();
        $notificationStyle->writeln($pr->htmlUrl());
        $notificationStyle->newLine();
    }

    private function renderLabel(Label $label): void
    {
        $colors = [
            'patch' => 'blue',
            'bug' => 'red',
            'docs' => 'yellow',
            'minor' => 'green',
            'pedantic' => 'cyan',
        ];

        $color = 'default';
        if (\array_key_exists($label->name(), $colors)) {
            $color = $colors[$label->name()];
        }

        $this->io->getErrorStyle()->write(sprintf(
            ' <fg=%s>[%s]</>',
            $color,
            $label->name()
        ));
    }

    private function renderStability(Stability $stability): void
    {
        $notificationStyle = $this->io->getErrorStyle();
        $stabilities = [
            'patch' => 'blue',
            'minor' => 'green',
            'pedantic' => 'yellow',
            'unknown' => 'red',
        ];

        if (\array_key_exists($stability->toString(), $stabilities)) {
            $notificationStyle->write(sprintf(
                '<fg=black;bg=%s>[%s]</> ',
                $stabilities[$stability->toString()],
                $stability->toUppercaseString()
            ));
        } else {
            $notificationStyle->write('<error>[NOT SET]</error> ');
        }
    }

    private function renderStatus(Status $status): void
    {
        $notificationStyle = $this->io->getErrorStyle();
        if ('success' === $status->state()) {
            $notificationStyle->writeln(sprintf(
                '    <info>%s</info>',
                $status->description()
            ));
        } elseif ('pending' === $status->state()) {
            $notificationStyle->writeln(sprintf(
                '    <comment>%s</comment>',
                $status->description()
            ));
        } else {
            $notificationStyle->writeln(sprintf(
                '    <error>%s</error>',
                $status->description()
            ));
        }

        $notificationStyle->text(sprintf(
            '     %s',
            $status->targetUrl()
        ));
        $notificationStyle->newLine();
    }
}
