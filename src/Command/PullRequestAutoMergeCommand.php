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
use App\Github\Api\Checks;
use App\Github\Api\Commits;
use App\Github\Api\PullRequests;
use App\Github\Api\References;
use App\Github\Api\Statuses;
use App\Github\Domain\Value\Commit\CommitCollection;
use App\Github\Domain\Value\PullRequest\Head\Repo;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Symfony\Component\String\u;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class PullRequestAutoMergeCommand extends AbstractNeedApplyCommand
{
    private Projects $projects;
    private PullRequests $pullRequests;
    private Statuses $statuses;
    private Checks $checks;
    private Commits $commits;
    private References $references;

    public function __construct(
        Projects $projects,
        PullRequests $pullRequests,
        Statuses $statuses,
        Checks $checks,
        Commits $commits,
        References $references
    ) {
        parent::__construct();

        $this->projects = $projects;
        $this->pullRequests = $pullRequests;
        $this->statuses = $statuses;
        $this->checks = $checks;
        $this->commits = $commits;
        $this->references = $references;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('pull-request-auto-merge')
            ->setDescription(sprintf(
                'Merge RTM pull requests. Only active for pull requests by %s.',
                self::SONATA_CI_BOT
            ));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title(sprintf(
            'Merge RTM pull requests (by %s)',
            self::SONATA_CI_BOT
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

        foreach ($this->pullRequests->all($repository) as $pr) {
            // Do not manage not configured branches.
            if (!\in_array(
                u($pr->base()->ref())->replace('-dev-kit', '')->toString(),
                $project->branchNames(),
                true
            )) {
                continue;
            }

            // Proceed only bot PR for now.
            if (self::SONATA_CI_BOT !== $pr->user()->login()) {
                continue;
            }

            $this->io->writeln(sprintf(
                '%s: <comment>%s (%s)</comment> by %s -> <comment>%s</comment>',
                $project->name(),
                $pr->title(),
                $pr->issue()->toString(),
                $pr->user()->login(),
                $pr->base()->ref()
            ));

            $combinedStatus = $this->statuses->combined(
                $repository,
                $pr->head()->sha()
            );

            $checkRuns = $this->checks->all(
                $repository,
                $pr->head()->sha()
            );

            $this->io->writeln(sprintf(
                '    Combined status successful? %s',
                $combinedStatus->isSuccessful() ? '<info>yes</info>' : '<error>no</error>'
            ));
            $this->io->writeln(sprintf(
                '    Checks successful?          %s',
                $checkRuns->isSuccessful() ? '<info>yes</info>' : '<error>no</error>'
            ));
            $this->io->newLine();

            // Ignore the PR for now if status is not good.
            if (!$combinedStatus->isSuccessful()
                || !$checkRuns->isSuccessful()
            ) {
                continue;
            }

            // Wait a bit to be sure the PR state is updated.
            if ($pr->updatedWithinTheLast60Seconds()) {
                continue;
            }

            $commits = CommitCollection::from(
                $this->commits->all($repository, $pr)
            );

            $uniqueCommitsCount = $commits->uniqueCount();

            // Some commit have the same message, but this cannot be squashed.
            if ($commits->count() !== $uniqueCommitsCount
                && 1 !== $uniqueCommitsCount
            ) {
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
                    $this->pullRequests->merge(
                        $repository,
                        $pr,
                        $squash,
                        $squash ? sprintf('%s (%s)', $commits->firstMessage(), $pr->issue()->toString()) : null
                    );

                    $repo = $pr->head()->repo();
                    if ($repo instanceof Repo
                        && 'sonata-project' === $repo->owner()->login()
                    ) {
                        $this->references->remove($repository, $pr);
                    }

                    $this->io->success(sprintf(
                        'Merged PR %s',
                        $pr->issue()->toString()
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
