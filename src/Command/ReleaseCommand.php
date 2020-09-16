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
use App\Domain\Value\Project;
use App\Domain\Value\Repository;
use Github\Client as GithubClient;
use Github\ResultPagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Webmozart\Assert\Assert;

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
    private GithubClient $github;
    private ResultPagerInterface $githubPager;

    public function __construct(Projects $projects, GithubClient $github, ResultPagerInterface $githubPager)
    {
        parent::__construct();

        $this->projects = $projects;
        $this->github = $github;
        $this->githubPager = $githubPager;
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

        $this->io->getErrorStyle()->title($project->name());

        $branches = $project->branchNames();
        $branch = \count($branches) > 1 ? next($branches) : current($branches);

        $this->prepareRelease($project, $branch, $output);

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

    private function prepareRelease(Project $project, string $branch, OutputInterface $output): void
    {
        Assert::stringNotEmpty($branch);

        $repository = $project->repository();

        $currentRelease = $this->github->repo()->releases()->latest(
            $repository->vendor(),
            $repository->name()
        );

        $branchToRelease = $this->github->repo()->branches(
            $repository->vendor(),
            $repository->name(),
            $branch
        );

        $statuses = $this->github->repo()->statuses()->combined(
            $repository->vendor(),
            $repository->name(),
            $branchToRelease['commit']['sha']
        );

        $pulls = $this->findPullRequestsSince(
            $currentRelease['published_at'],
            $repository,
            $branch
        );

        $nextVersion = $this->determineNextVersion($currentRelease['tag_name'], $pulls);
        $changelog = array_reduce(
            array_filter(array_column($pulls, 'changelog')),
            'array_merge_recursive',
            []
        );

        $errorOutput = $this->io->getErrorStyle();
        $errorOutput->section('Project');

        foreach ($statuses['statuses'] as $status) {
            $print = $status['description']."\n".$status['target_url'];

            if ('success' === $status['state']) {
                $errorOutput->success($print);
            } elseif ('pending' === $status['state']) {
                $errorOutput->warning($print);
            } else {
                $errorOutput->error($print);
            }
        }

        $errorOutput->section('Pull requests');

        foreach ($pulls as $pull) {
            $this->printPullRequest($pull, $errorOutput);
        }

        $errorOutput->section('Release');

        if ($nextVersion === $currentRelease['tag_name']) {
            $errorOutput->warning('Release is not needed');
        } else {
            $errorOutput->success('Next release will be: '.$nextVersion);

            $errorOutput->section('Changelog');

            $this->printRelease($currentRelease['tag_name'], $nextVersion, $repository, $output);
            $this->printChangelog($changelog, $output);
        }
    }

    private function printPullRequest(array $pull, OutputInterface $output): void
    {
        if (\array_key_exists($pull['stability'], static::$stabilities)) {
            $output->write('<fg=black;bg='.static::$stabilities[$pull['stability']].'>['
                .strtoupper($pull['stability']).']</> ');
        } else {
            $output->write('<error>[NOT SET]</error> ');
        }
        $output->write('<info>'.$pull['title'].'</info>');

        foreach ($pull['labels'] as $label) {
            if (!\array_key_exists($label['name'], static::$labels)) {
                $output->write(' <error>['.$label['name'].']</error>');
            } else {
                $output->write(' <fg='.static::$labels[$label['name']].'>['.$label['name'].']</>');
            }
        }

        if (empty($pull['labels'])) {
            $output->write(' <fg=black;bg=yellow>[No labels]</>');
        }

        if (!$pull['changelog'] && 'pedantic' !== $pull['stability']) {
            $output->write(' <error>[Changelog not found]</error>');
        } elseif (!$pull['changelog']) {
            $output->write(' <fg=black;bg=green>[Changelog not found]</>');
        } elseif ($pull['changelog'] && 'pedantic' === $pull['stability']) {
            $output->write(' <fg=black;bg=yellow>[Changelog found]</>');
        } else {
            $output->write(' <fg=black;bg=green>[Changelog found]</>');
        }
        $output->writeln('');
        $output->writeln($pull['html_url']);
        $output->writeln('');
    }

    private function printRelease(string $currentVersion, string $nextVersion, Repository $repository, OutputInterface $output): void
    {
        $output->writeln(sprintf(
            '## [%s](%s/compare/%s...%s) - %s',
            $nextVersion,
            $repository->toString(),
            $currentVersion,
            $nextVersion,
            date('Y-m-d')
        ));
    }

    private function printChangelog(array $changelog, OutputInterface $output): void
    {
        ksort($changelog);
        foreach ($changelog as $type => $changes) {
            if (0 === \count($changes)) {
                continue;
            }

            $output->writeln('### '.$type);

            foreach ($changes as $change) {
                $output->writeln($change);
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

    private function determineNextVersion(string $currentVersion, array $pulls): string
    {
        $stabilities = array_column($pulls, 'stability');
        $parts = explode('.', $currentVersion);

        if (\in_array('minor', $stabilities, true)) {
            return implode('.', [$parts[0], (int) $parts[1] + 1, 0]);
        } elseif (\in_array('patch', $stabilities, true)) {
            return implode('.', [$parts[0], $parts[1], (int) $parts[2] + 1]);
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

    private function findPullRequestsSince(string $date, Repository $repository, string $branch): array
    {
        Assert::stringNotEmpty($branch);

        $pulls = $this->githubPager->fetchAll($this->github->search(), 'issues', [sprintf(
            'repo:%s type:pr is:merged base:%s merged:>%s',
            $repository->toString(),
            $branch,
            $date
        )]);

        $filteredPulls = [];
        foreach ($pulls as $pull) {
            if (self::BOT_NAME === $pull['user']['login']) {
                continue;
            }

            $pull['changelog'] = $this->parseChangelog($pull);
            $pull['stability'] = $this->determinePullRequestStability($pull);

            $filteredPulls[] = $pull;
        }

        return $filteredPulls;
    }
}
