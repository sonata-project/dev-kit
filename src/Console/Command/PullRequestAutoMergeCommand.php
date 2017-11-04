<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DevKit\Console\Command;

use Github\Exception\ExceptionInterface;
use Packagist\Api\Result\Package;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class PullRequestAutoMergeCommand extends AbstractNeedApplyCommand
{
    private const TIME_BEFORE_MERGE = 60;

    protected function configure()
    {
        parent::configure();

        $this
            ->setName('pull-request-auto-merge')
            ->setDescription('Merge RTM pull requests. Only active for StyleCI/SonataCI PR.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->configs['projects'] as $name => $projectConfig) {
            try {
                $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);
                $this->io->title($package->getName());
                $this->mergePullRequest($package, $projectConfig);
            } catch (ExceptionInterface $e) {
                $this->io->error('Failed with message: '.$e->getMessage());
            }
        }

        return 0;
    }

    private function mergePullRequest(Package $package, array $projectConfig)
    {
        if (!array_key_exists('branches', $projectConfig)) {
            return;
        }

        $repositoryName = $this->getRepositoryName($package);
        $branches = array_keys($projectConfig['branches']);

        $pulls = $this->githubPaginator->fetchAll($this->githubClient->pullRequests(), 'all', [
            static::GITHUB_GROUP,
            $repositoryName,
        ]);
        foreach ($pulls as $pull) {
            // Do not managed not configured branches.
            if (!in_array(str_replace('-dev-kit', '', $pull['base']['ref']), $branches, true)) {
                continue;
            }

            // Proceed only bot PR for now.
            if ('SonataCI' !== $pull['user']['login']) {
                continue;
            }

            $this->io->section(sprintf(
                '#%d > %s - %s',
                $pull['number'],
                $pull['base']['ref'],
                $pull['title']
            ));

            $state = $this->githubClient->repos()->statuses()->combined(
                static::GITHUB_GROUP,
                $repositoryName,
                $pull['head']['sha']
            );

            $this->io->comment('Author: '.$pull['user']['login']);
            $this->io->comment('Branch: '.$pull['base']['ref']);
            $this->io->comment('Status: '.$state['state']);

            // Ignore the PR is status is not good.
            if ('success' !== $state['state']) {
                continue;
            }

            $updatedAt = new \DateTime($pull['updated_at'], new \DateTimeZone('UTC'));
            // Wait a bit to be sure the PR state is updated.
            if ((new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp()
                - $updatedAt->getTimestamp() < self::TIME_BEFORE_MERGE) {
                continue;
            }

            $commits = $this->githubPaginator->fetchAll($this->githubClient->pullRequests(), 'commits', [
                static::GITHUB_GROUP,
                $repositoryName,
                $pull['number'],
            ]);

            $commitMessages = array_map(function ($commit) {
                return $commit['commit']['message'];
            }, $commits);
            $commitsCount = count($commitMessages);
            $uniqueCommitsCount = count(array_unique($commitMessages));

            // Some commit have the same message, but this cannot be squashed.
            if ($commitsCount !== $uniqueCommitsCount && 1 !== $uniqueCommitsCount) {
                $this->io->caution('This PR need a manual rebase.');

                continue;
            }
            $squash = 1 === $uniqueCommitsCount;

            $this->io->comment('Squash: '.($squash ? 'yes' : 'no'));

            if ($this->apply) {
                try {
                    $this->githubClient->pullRequests()->merge(
                        static::GITHUB_GROUP,
                        $repositoryName,
                        $pull['number'],
                        $squash ? '' : $pull['title'],
                        $pull['head']['sha'],
                        $squash,
                        $squash ? sprintf('%s (#%d)', $commitMessages[0], $pull['number']) : null
                    );

                    if ('sonata-project' === $pull['head']['repo']['owner']['login']) {
                        $this->githubClient->gitData()->references()->remove(
                            static::GITHUB_GROUP,
                            $repositoryName,
                            'heads/'.$pull['head']['ref']
                        );
                    }

                    $this->io->success(sprintf('Merged PR #%d', $pull['number']));
                } catch (ExceptionInterface $e) {
                    $this->io->error('Failed with message: '.$e->getMessage());
                }
            }
        }
    }
}
