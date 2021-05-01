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

namespace App\Action;

use App\Action\Exception\CannotDetermineNextRelease;
use App\Action\Exception\NoPullRequestsMergedSinceLastRelease;
use App\Command\AbstractCommand;
use App\Domain\Value\Branch;
use App\Domain\Value\NextRelease;
use App\Domain\Value\Project;
use App\Github\Api\Branches;
use App\Github\Api\Checks;
use App\Github\Api\PullRequests;
use App\Github\Api\Releases;
use App\Github\Api\Statuses;
use App\Github\Domain\Value\Search\Query;
use App\Github\Exception\LatestReleaseNotFound;

final class DetermineNextRelease
{
    private Releases $releases;
    private Branches $branches;
    private Statuses $statuses;
    private Checks $checks;
    private PullRequests $pullRequests;

    public function __construct(
        Releases $releases,
        Branches $branches,
        Statuses $statuses,
        Checks $checks,
        PullRequests $pullRequests
    ) {
        $this->releases = $releases;
        $this->branches = $branches;
        $this->statuses = $statuses;
        $this->checks = $checks;
        $this->pullRequests = $pullRequests;
    }

    public function __invoke(Project $project, Branch $branch): NextRelease
    {
        $repository = $project->repository();

        try {
            $currentRelease = $this->releases->branchLatest($repository, $branch);
        } catch (LatestReleaseNotFound $e) {
            throw CannotDetermineNextRelease::forBranch(
                $project,
                $branch,
                $e
            );
        }

        $pullRequests = $this->pullRequests->search(
            $repository,
            Query::pullRequestsSince($repository, $branch, $currentRelease->publishedAt(), AbstractCommand::SONATA_CI_BOT)
        );

        if ([] === $pullRequests) {
            throw NoPullRequestsMergedSinceLastRelease::forBranch(
                $project,
                $branch,
                $currentRelease->publishedAt()
            );
        }

        $branchToRelease = $this->branches->get(
            $repository,
            $branch->name()
        );

        $combinedStatus = $this->statuses->combined(
            $repository,
            $branchToRelease->commit()->sha()
        );

        $checkRuns = $this->checks->all(
            $repository,
            $branchToRelease->commit()->sha()
        );

        return NextRelease::fromValues(
            $project,
            $branch,
            $currentRelease->tag(),
            $combinedStatus,
            $checkRuns,
            $pullRequests
        );
    }
}
