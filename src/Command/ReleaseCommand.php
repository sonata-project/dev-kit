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
        $changelog = array_reduce(
            array_filter(array_column($pulls, 'changelog')),
            'array_merge_recursive',
            []
        );

        $this->io->section('Project');

        foreach ($combined->statuses() as $status) {
            $print = $status->description()."\n".$status->targetUrl();

            if ('success' === $status->state()) {
                $this->io->success($print);
            } elseif ('pending' === $status->state()) {
                $this->io->warning($print);
            } else {
                $this->io->error($print);
            }
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

            $this->printChangelog($changelog);
        }
    }

    private function printPullRequest(array $pull): void
    {
        if (\array_key_exists($pull['stability'], static::$stabilities)) {
            $this->io->write('<fg=black;bg='.static::$stabilities[$pull['stability']].'>['
                .strtoupper($pull['stability']).']</> ');
        } else {
            $this->io->write('<error>[NOT SET]</error> ');
        }
        $this->io->write('<info>'.$pull['title'].'</info>');

        foreach ($pull['labels'] as $label) {
            if (!\array_key_exists($label['name'], static::$labels)) {
                $this->io->write(' <error>['.$label['name'].']</error>');
            } else {
                $this->io->write(' <fg='.static::$labels[$label['name']].'>['.$label['name'].']</>');
            }
        }

        if (empty($pull['labels'])) {
            $this->io->write(' <fg=black;bg=yellow>[No labels]</>');
        }

        if (!$pull['changelog'] && 'pedantic' !== $pull['stability']) {
            $this->io->write(' <error>[Changelog not found]</error>');
        } elseif (!$pull['changelog']) {
            $this->io->write(' <fg=black;bg=green>[Changelog not found]</>');
        } elseif ($pull['changelog'] && 'pedantic' === $pull['stability']) {
            $this->io->write(' <fg=black;bg=yellow>[Changelog found]</>');
        } else {
            $this->io->write(' <fg=black;bg=green>[Changelog found]</>');
        }
        $this->io->writeln('');
        $this->io->writeln($pull['html_url']);
        $this->io->writeln('');
    }

    private function printRelease(Repository $repository, Tag $currentVersion, Tag $next): void
    {
        $this->io->writeln(sprintf(
            '## [%s](%s/compare/%s...%s) - %s',
            $next->toString(),
            $repository->toString(),
            $currentVersion->toString(),
            $next,
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

    private function parseChangelog(array $pull): array
    {
        $changelog = [];
        $body = preg_replace('/<!--(.*)-->/Uis', '', $pull['body']);
        preg_match('/## Changelog.*```\s*markdown\s*\\n(.*)\\n```/Uis', $body, $matches);

        if (2 === \count($matches)) {
            $lines = explode(PHP_EOL, $matches[1]);

            $section = '';
            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                if (0 === strpos($line, '#')) {
                    $section = preg_replace('/^#* /i', '', $line);
                } elseif (!empty($section)) {
                    $line = preg_replace('/^- /i', '', $line);
                    $changelog[$section][] = '- [[#'.$pull['number'].']('.$pull['html_url'].')] '.
                        ucfirst($line).' ([@'.$pull['user']['login'].']('.$pull['user']['html_url'].'))';
                }
            }
        }

        return $changelog;
    }

    private function determineNextVersion(Tag $currentVersion, array $pulls): Tag
    {
        $stabilities = array_column($pulls, 'stability');
        $parts = explode('.', $currentVersion->toString());

        if (\in_array('minor', $stabilities, true)) {
            return Tag::fromString(implode('.', [$parts[0], (int) $parts[1] + 1, 0]));
        } elseif (\in_array('patch', $stabilities, true)) {
            return Tag::fromString(implode('.', [$parts[0], $parts[1], (int) $parts[2] + 1]));
        }

        return $currentVersion;
    }

    private function determinePullRequestStability(array $pull): string
    {
        $labels = array_column($pull['labels'], 'name');

        if (\in_array('minor', $labels, true)) {
            return 'minor';
        } elseif (\in_array('patch', $labels, true)) {
            return 'patch';
        } elseif (array_intersect(['docs', 'pedantic'], $labels)) {
            return 'pedantic';
        }

        return 'unknown';
    }

    private function findPullRequestsSince(\DateTimeImmutable $date, Repository $repository, Branch $branch): array
    {
        $query = Query::fromString(sprintf(
            'repo:%s type:pr is:merged base:%s merged:>%s -author:%s',
            $repository->toString(),
            $branch->name(),
            $date->format('Y-m-d\TH:i:s\Z'), // @todo check if there is a better way to format the datetime like this
            self::BOT_NAME
        ));

        $pulls = $this->pullRequests->search($query);

        $extendedPulls = [];
        foreach ($pulls as $pull) {
            $pull['changelog'] = $this->parseChangelog($pull);
            $pull['stability'] = $this->determinePullRequestStability($pull);

            $extendedPulls[] = $pull;
        }

        return $extendedPulls;
    }
}
