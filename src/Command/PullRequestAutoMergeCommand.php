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

use App\Domain\Value\Project;
use App\Github\Domain\Value\PullRequest;
use App\Github\Domain\Value\Status;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Symfony\Component\String\u;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class PullRequestAutoMergeCommand extends AbstractNeedApplyCommand
{
    private const TIME_BEFORE_MERGE = 60;

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('pull-request-auto-merge')
            ->setDescription('Merge RTM pull requests. Only active for oull requests by SonataCI.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->configs['projects'] as $name => $config) {
            try {
                $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);

                $this->io->title($package->getName());
                $project = Project::fromValues($name, $config, $package);

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
        if (!$project->hasBranches()) {
            return;
        }

        $repository = $project->repository();

        $brancheNames = $project->branchNames();

        $pullRequests = [];
        foreach ($this->githubPaginator->fetchAll($this->githubClient->pullRequests(), 'all', [
            static::GITHUB_GROUP,
            $repository->name(),
        ]) as $pull) {
            $pullRequests[] = PullRequest::fromResponse($pull);
        }

        /** @var PullRequest $pullRequest */
        foreach ($pullRequests as $pullRequest) {
            // Do not manage not configured branches.
            if (!\in_array(u($pullRequest->base()->ref())->replace('-dev-kit', '')->toString(), $brancheNames, true)) {
                continue;
            }

            // Proceed only bot PR for now.
            if (self::BOT_NAME !== $pullRequest->user()->login()) {
                continue;
            }

            $this->io->section(sprintf(
                '#%d > %s - %s',
                $pullRequest->number(),
                $pullRequest->base()->ref(),
                $pullRequest->title()
            ));

            $state = $this->githubClient->repos()->statuses()->combined(
                static::GITHUB_GROUP,
                $repository->name(),
                $pullRequest->head()->sha()
            );

            $status = Status::fromResponse($state);

            $this->io->comment(sprintf('Author: %s', $pullRequest->user()->login()));
            $this->io->comment(sprintf('Branch: %s', $pullRequest->base()->ref()));
            $this->io->comment(sprintf('Status: %s', $status->state()));

            // Ignore the PR if status is not good.
            if (!$status->isSuccessful()) {
                continue;
            }

            // Wait a bit to be sure the PR state is updated.
            if ((new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp()
                - $pullRequest->updatedAt()->getTimestamp() < self::TIME_BEFORE_MERGE
            ) {
                continue;
            }

            $commits = $this->githubPaginator->fetchAll($this->githubClient->pullRequests(), 'commits', [
                static::GITHUB_GROUP,
                $repository->name(),
                $pullRequest->number(),
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
                    $this->githubClient->pullRequests()->merge(
                        static::GITHUB_GROUP,
                        $repository->name(),
                        $pullRequest->number(),
                        $squash ? '' : $pullRequest->title(),
                        $pullRequest->head()->sha(),
                        $squash,
                        $squash ? sprintf('%s (#%d)', $commitMessages[0], $pullRequest->number()) : null
                    );

                    if ('sonata-project' === $pullRequest->head()->repo()->owner()->login()) {
                        $this->githubClient->gitData()->references()->remove(
                            static::GITHUB_GROUP,
                            $repository->name(),
                            'heads/'.$pullRequest->head()->ref()
                        );
                    }

                    $this->io->success(sprintf(
                        'Merged PR #%d',
                        $pullRequest->number()
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
