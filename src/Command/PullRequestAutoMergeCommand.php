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
use App\Github\Api\PullRequests;
use App\Github\Domain\Value\PullRequest\CombinedStatus;
use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use Github\ResultPagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Symfony\Component\String\u;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class PullRequestAutoMergeCommand extends AbstractNeedApplyCommand
{
    private const TIME_BEFORE_MERGE = 60;

    private Projects $projects;
    private PullRequests $pullRequests;
    private GithubClient $github;
    private ResultPagerInterface $githubPager;

    public function __construct(
        Projects $projects,
        PullRequests $pullRequests,
        GithubClient $github,
        ResultPagerInterface $githubPager
    ) {
        parent::__construct();

        $this->projects = $projects;
        $this->pullRequests = $pullRequests;
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

                $this->mergePullRequests($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function mergePullRequests(Project $project): void
    {
        if (!$project->hasBranches()) {
            $this->io->comment('No branches defined.');

            return;
        }

        $repository = $project->repository();

        foreach ($this->pullRequests->all($repository) as $pullRequest) {
            // Do not manage not configured branches.
            if (!\in_array(u($pullRequest->base()->ref())->replace('-dev-kit', '')->toString(), $project->branchNames(), true)) {
                continue;
            }

            // Proceed only bot PR for now.
            if (self::BOT_NAME !== $pullRequest->user()->login()) {
                continue;
            }

            $this->io->writeln(sprintf(
                '%s: <comment>%s (#%d)</comment> by %s -> <comment>%s</comment>',
                $project->name(),
                $pullRequest->title(),
                $pullRequest->issue()->toInt(),
                $pullRequest->user()->login(),
                $pullRequest->base()->ref()
            ));

            $combinedStatusResponse = $this->github->repos()->statuses()->combined(
                $repository->vendor(),
                $repository->name(),
                $pullRequest->head()->sha()
            );

            $combinedStatus = CombinedStatus::fromResponse($combinedStatusResponse);

            $this->io->writeln(sprintf(
                '    Combined status: %s',
                $combinedStatus->toString()
            ));
            $this->io->newLine();

            // Ignore the PR for now if status is not good.
            if (!$combinedStatus->isSuccessful()) {
                continue;
            }

            // Wait a bit to be sure the PR state is updated.
            if ((new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp()
                - $pullRequest->updatedAt()->getTimestamp() < self::TIME_BEFORE_MERGE
            ) {
                continue;
            }

            $commits = $this->githubPager->fetchAll($this->github->pullRequests(), 'commits', [
                $repository->vendor(),
                $repository->name(),
                $pullRequest->issue()->toInt(),
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
                        $repository->vendor(),
                        $repository->name(),
                        $pullRequest->issue()->toInt(),
                        $squash ? '' : $pullRequest->title(),
                        $pullRequest->head()->sha(),
                        $squash,
                        $squash ? sprintf('%s (#%d)', $commitMessages[0], $pullRequest->issue()->toInt()) : null
                    );

                    if ('sonata-project' === $pullRequest->head()->repo()->owner()->login()) {
                        $this->github->gitData()->references()->remove(
                            $repository->vendor(),
                            $repository->name(),
                            u('heads/')->append($pullRequest->head()->ref())->toString()
                        );
                    }

                    $this->io->success(sprintf(
                        'Merged PR #%d',
                        $pullRequest->issue()->toInt()
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
