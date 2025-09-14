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

namespace App\Domain\Value;

use App\Action\DetermineNextReleaseVersion;
use App\Domain\Exception\CannotGenerateChangelog;
use App\Github\Domain\Value\CheckRuns;
use App\Github\Domain\Value\CombinedStatus;
use App\Github\Domain\Value\PullRequest;
use App\Github\Domain\Value\Release\Tag;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class NextRelease
{
    private Tag $nextTag;

    /**
     * @param PullRequest[] $pullRequests
     */
    private function __construct(
        private Project $project,
        private Branch $branch,
        private Tag $currentTag,
        private CombinedStatus $combinedStatus,
        private CheckRuns $checkRuns,
        private array $pullRequests,
    ) {
        $this->nextTag = DetermineNextReleaseVersion::forTagAndPullRequests(
            $this->currentTag,
            $this->pullRequests
        );
    }

    /**
     * @param PullRequest[] $pullRequests
     */
    public static function fromValues(
        Project $project,
        Branch $branch,
        Tag $currentTag,
        CombinedStatus $combinedStatus,
        CheckRuns $checkRuns,
        array $pullRequests,
    ): self {
        return new self(
            $project,
            $branch,
            $currentTag,
            $combinedStatus,
            $checkRuns,
            $pullRequests
        );
    }

    public function project(): Project
    {
        return $this->project;
    }

    public function branch(): Branch
    {
        return $this->branch;
    }

    public function currentTag(): Tag
    {
        return $this->currentTag;
    }

    public function nextTag(): Tag
    {
        return $this->nextTag;
    }

    public function combinedStatus(): CombinedStatus
    {
        return $this->combinedStatus;
    }

    public function checkRuns(): CheckRuns
    {
        return $this->checkRuns;
    }

    /**
     * @return PullRequest[]
     */
    public function pullRequests(): array
    {
        return $this->pullRequests;
    }

    /**
     * @return PullRequest[]
     */
    public function pullRequestsWithoutStabilityLabel(): array
    {
        return array_reduce($this->pullRequests(), static function (array $pullRequests, PullRequest $pullRequest): array {
            if ($pullRequest->stability()->notEquals(Stability::unknown())) {
                return $pullRequests;
            }

            $pullRequests[] = $pullRequest;

            return $pullRequests;
        }, []);
    }

    /**
     * @return PullRequest[]
     */
    public function pullRequestsWithoutChangelog(): array
    {
        return array_reduce($this->pullRequests(), static function (array $pullRequests, PullRequest $pullRequest): array {
            if (!$pullRequest->needsChangelog() || $pullRequest->hasChangelog()) {
                return $pullRequests;
            }

            $pullRequests[] = $pullRequest;

            return $pullRequests;
        }, []);
    }

    public function changelog(): Changelog
    {
        if (!$this->canBeReleased()) {
            throw CannotGenerateChangelog::forRelease($this);
        }

        return Changelog::fromPullRequests(
            $this->pullRequests,
            $this->nextTag,
            $this->currentTag,
            $this->project->package()
        );
    }

    public function isNeeded(): bool
    {
        if ($this->project->package()->isAbandoned()) {
            return false;
        }

        return $this->nextTag->toString() !== $this->currentTag->toString();
    }

    public function canBeReleased(): bool
    {
        return $this->isNeeded()
            && [] !== $this->pullRequests()
            && [] === $this->pullRequestsWithoutStabilityLabel()
            && [] === $this->pullRequestsWithoutChangelog()
            && $this->stability()->notEquals(Stability::pedantic());
    }

    public function stability(): Stability
    {
        $stabilities = array_map(
            static fn (PullRequest $pr): string => $pr->stability()->toString(),
            $this->pullRequests
        );

        if (\in_array(Stability::major()->toString(), $stabilities, true)) {
            return Stability::major();
        }

        if (\in_array(Stability::minor()->toString(), $stabilities, true)) {
            return Stability::minor();
        }

        if (\in_array(Stability::patch()->toString(), $stabilities, true)) {
            return Stability::patch();
        }

        return Stability::pedantic();
    }
}
