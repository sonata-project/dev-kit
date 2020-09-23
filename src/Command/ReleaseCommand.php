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

use App\Config\Projects;
use App\Domain\Value\Branch;
use App\Domain\Value\Project;
use App\Domain\Value\Repository;
use App\Github\Api\Branches;
use App\Github\Api\PullRequests;
use App\Github\Api\Releases;
use App\Github\Api\Statuses;
use App\Github\Domain\Value\PullRequest;
use App\Github\Domain\Value\Release\Tag;
use App\Github\Domain\Value\Search\Query;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Jordi Sala <jordism91@gmail.com>
 */
final class ReleaseCommand extends AbstractCommand
{
    /**
     * @var array<string, string>
     */
    private static $labels = [
        'patch' => 'blue',
        'bug' => 'red',
        'docs' => 'yellow',
        'minor' => 'green',
        'pedantic' => 'cyan',
    ];

    /**
     * @var array<string, string>
     */
    private static $stabilities = [
        'patch' => 'blue',
        'minor' => 'green',
        'pedantic' => 'yellow',
        'unknown' => 'red',
    ];

    private Projects $projects;
    private Releases $releases;
    private Branches $branches;
    private Statuses $statuses;
    private PullRequests $pullRequests;

    public function __construct(
        Projects $projects,
        Releases $releases,
        Branches $branches,
        Statuses $statuses,
        PullRequests $pullRequests
    ) {
        parent::__construct();

        $this->projects = $projects;
        $this->releases = $releases;
        $this->branches = $branches;
        $this->statuses = $statuses;
        $this->pullRequests = $pullRequests;
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
the projects configured on <info>projects.yaml</info>

The command will show what is the status of the project, then a list of pull requests
made against selected branch (default: stable branch) with the following information:

stability, name, labels, changelog, url.

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

        $this->io->title($project->name());

        $branches = $project->branches();
        $branch = \count($branches) > 1 ? next($branches) : current($branches);

        $this->prepareRelease($project, $branch);

        return 0;
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

    private function prepareRelease(Project $project, Branch $branch): void
    {
        $repository = $project->repository();

        $currentRelease = $this->releases->latest($repository);

        $branchToRelease = $this->branches->get(
            $repository,
            $branch->name()
        );

        $combined = $this->statuses->combined(
            $repository,
            $branchToRelease->commit()->sha()
        );

        $pulls = $this->findPullRequestsSince(
            $currentRelease->publishedAt(),
            $repository,
            $branch
        );

        $next = $this->determineNextVersion($currentRelease->tag(), $pulls);

        $this->io->section('Checks');

        foreach ($combined->statuses() as $status) {
            if ('success' === $status->state()) {
                $this->io->writeln(sprintf(
                    '    <info>%s</info>',
                    $status->description()
                ));
            } elseif ('pending' === $status->state()) {
                $this->io->writeln(sprintf(
                    '    <comment>%s</comment>',
                    $status->description()
                ));
            } else {
                $this->io->writeln(sprintf(
                    '    <error>%s</error>',
                    $status->description()
                ));
            }

            $this->io->text(sprintf(
                '     %s',
                $status->targetUrl()
            ));
            $this->io->newLine();
        }

        $this->io->section('Pull requests');

        foreach ($pulls as $pull) {
            $this->printPullRequest($pull);
        }

        $this->io->section('Release');

        if ($next->toString() === $currentRelease->tag()->toString()) {
            $this->io->warning('Release is not needed');
        } else {
            $this->io->success(sprintf(
                'Next release will be: %s',
                $next->toString()
            ));

            $this->io->section('Changelog');

            $this->printRelease(
                $repository,
                $currentRelease->tag(),
                $next
            );

            $changelogs = array_map(static function (PullRequest $pr): array {
                return $pr->changelog();
            }, $pulls);

            $changelog = array_reduce(
                $changelogs,
                'array_merge_recursive',
                []
            );

            $this->printChangelog($changelog);
        }
    }

    private function printPullRequest(PullRequest $pr): void
    {
        if (\array_key_exists($pr->stability(), static::$stabilities)) {
            $this->io->write(sprintf(
                '<fg=black;bg=%s>[%s]</> ',
                static::$stabilities[$pr->stability()],
                strtoupper($pr->stability())
            ));
        } else {
            $this->io->write('<error>[NOT SET]</error> ');
        }
        $this->io->write(sprintf(
            '<info>%s</info>',
            $pr->title()
        ));

        foreach ($pr->labels() as $label) {
            if (!\array_key_exists($label->name(), static::$labels)) {
                $this->io->write(sprintf(
                    ' <error>[%s]</error>',
                    $label->name()
                ));
            } else {
                $this->io->write(sprintf(
                    ' <fg=%s>[%s]</>',
                    static::$labels[$label->name()],
                    $label->name()
                ));
            }
        }

        if (!$pr->hasLabels()) {
            $this->io->write(' <fg=black;bg=yellow>[No labels]</>');
        }

        if (!$pr->changelog() && 'pedantic' !== $pr->stability()) {
            $this->io->write(' <error>[Changelog not found]</error>');
        } elseif (!$pr->changelog()) {
            $this->io->write(' <fg=black;bg=green>[Changelog not found]</>');
        } elseif ($pr->changelog() && 'pedantic' === $pr->stability()) {
            $this->io->write(' <fg=black;bg=yellow>[Changelog found]</>');
        } else {
            $this->io->write(' <fg=black;bg=green>[Changelog found]</>');
        }
        $this->io->newLine();
        $this->io->writeln($pr->htmlUrl());
        $this->io->newLine();
    }

    private function printRelease(Repository $repository, Tag $current, Tag $next): void
    {
        $this->io->writeln(sprintf(
            '## [%s](%s/compare/%s...%s) - %s',
            $next->toString(),
            $repository->toString(),
            $current->toString(),
            $next->toString(),
            date('Y-m-d')
        ));
    }

    private function printChangelog(array $changelog): void
    {
        ksort($changelog);
        foreach ($changelog as $type => $changes) {
            if (0 === \count($changes)) {
                continue;
            }

            $this->io->writeln(sprintf(
                '### %s',
                $type
            ));

            foreach ($changes as $change) {
                $this->io->writeln($change);
            }
            $this->io->newLine();
        }
    }

    /**
     * @param PullRequest[] $pullRequests
     */
    private function determineNextVersion(Tag $currentVersion, array $pullRequests): Tag
    {
        $stabilities = array_map(static function (PullRequest $pr): string {
            return $pr->stability();
        }, $pullRequests);

        $parts = explode('.', $currentVersion->toString());

        if (\in_array('minor', $stabilities, true)) {
            return Tag::fromString(implode('.', [$parts[0], (int) $parts[1] + 1, 0]));
        }

        if (\in_array('patch', $stabilities, true)) {
            return Tag::fromString(implode('.', [$parts[0], $parts[1], (int) $parts[2] + 1]));
        }

        return $currentVersion;
    }

    /**
     * @return PullRequest[]
     */
    private function findPullRequestsSince(\DateTimeImmutable $date, Repository $repository, Branch $branch): array
    {
        $query = Query::fromString(sprintf(
            'repo:%s type:pr is:merged base:%s merged:>%s -author:%s',
            $repository->toString(),
            $branch->name(),
            $date->format('Y-m-d\TH:i:s\Z'), // @todo check if there is a better way to format the datetime like this
            self::BOT_NAME
        ));

        return $this->pullRequests->search($repository, $query);
    }
}
