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
use App\Util\Util;
use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use Github\ResultPagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class PullRequestAutoMergeCommand extends AbstractNeedApplyCommand
{
    private const TIME_BEFORE_MERGE = 60;

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

        $this
            ->setName('pull-request-auto-merge')
            ->setDescription(sprintf(
                'Merge RTM pull requests. Only active for pull requests by %s.',
                self::BOT_NAME
            ))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title(sprintf(
            'Merge RTM pull requests (by %s)',
            self::BOT_NAME
        ));

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->section($project->name());

                $this->mergePullRequest($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function mergePullRequest(Project $project): void
    {
        $package = $project->package();
        $projectConfig = $project->rawConfig();

        if (!\array_key_exists('branches', $projectConfig)) {
            $this->io->comment('No branches defined.');

            return;
        }

        $repositoryName = Util::getRepositoryNameWithoutVendorPrefix($package);
        $branches = array_keys($projectConfig['branches']);

        $pulls = $this->githubPager->fetchAll($this->github->pullRequests(), 'all', [
            static::GITHUB_GROUP,
            $repositoryName,
        ]);

        foreach ($pulls as $pull) {
            // Do not manage not configured branches.
            if (!\in_array(str_replace('-dev-kit', '', $pull['base']['ref']), $branches, true)) {
                continue;
            }

            // Proceed only bot PR for now.
            if (self::BOT_NAME !== $pull['user']['login']) {
                continue;
            }

            $this->io->section(sprintf(
                '#%d > %s - %s',
                $pull['number'],
                $pull['base']['ref'],
                $pull['title']
            ));

            $state = $this->github->repos()->statuses()->combined(
                static::GITHUB_GROUP,
                $repositoryName,
                $pull['head']['sha']
            );

            $this->io->comment(sprintf('Author: %s', $pull['user']['login']));
            $this->io->comment(sprintf('Branch: %s', $pull['base']['ref']));
            $this->io->comment(sprintf('Status: %s', $state['state']));

            // Ignore the PR if status is not good.
            if ('success' !== $state['state']) {
                continue;
            }

            $updatedAt = new \DateTime($pull['updated_at'], new \DateTimeZone('UTC'));
            // Wait a bit to be sure the PR state is updated.
            if ((new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp()
                - $updatedAt->getTimestamp() < self::TIME_BEFORE_MERGE) {
                continue;
            }

            $commits = $this->githubPager->fetchAll($this->github->pullRequests(), 'commits', [
                static::GITHUB_GROUP,
                $repositoryName,
                $pull['number'],
            ]);

            $commitMessages = array_map(static function ($commit): string {
                return $commit['commit']['message'];
            }, $commits);
            $commitsCount = \count($commitMessages);
            $uniqueCommitsCount = \count(array_unique($commitMessages));

            // Some commit have the same message, but this cannot be squashed.
            if ($commitsCount !== $uniqueCommitsCount && 1 !== $uniqueCommitsCount) {
                $this->io->caution('This PR need a manual rebase.');

                continue;
            }
            $squash = 1 === $uniqueCommitsCount;

            $this->io->comment(sprintf(
                'Squash: %s',
                $squash ? 'yes' : 'no'
            ));

            if ($this->apply) {
                try {
                    $this->github->pullRequests()->merge(
                        static::GITHUB_GROUP,
                        $repositoryName,
                        $pull['number'],
                        $squash ? '' : $pull['title'],
                        $pull['head']['sha'],
                        $squash,
                        $squash ? sprintf('%s (#%d)', $commitMessages[0], $pull['number']) : null
                    );

                    if ('sonata-project' === $pull['head']['repo']['owner']['login']) {
                        $this->github->gitData()->references()->remove(
                            static::GITHUB_GROUP,
                            $repositoryName,
                            'heads/'.$pull['head']['ref']
                        );
                    }

                    $this->io->success(sprintf(
                        'Merged PR #%d',
                        $pull['number']
                    ));
                } catch (ExceptionInterface $e) {
                    $this->io->error(sprintf(
                        'Failed with message: %s',
                        $e->getMessage()
                    ));
                }
            }
        }
    }
}
