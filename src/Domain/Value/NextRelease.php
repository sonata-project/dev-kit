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
use App\Github\Domain\Value\CombinedStatus;
use App\Github\Domain\Value\PullRequest;
use App\Github\Domain\Value\Release\Tag;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class NextRelease
{
    private Project $project;
    private Tag $currentTag;
    private CombinedStatus $combinedStatus;

    /**
     * @var PullRequest[]
     */
    private array $pullRequests;

    private Tag $nextTag;

    private function __construct(
        Project $project,
        Tag $currentTag,
        CombinedStatus $combinedStatus,
        array $pullRequests
    ) {
        $this->project = $project;
        $this->currentTag = $currentTag;

        $this->combinedStatus = $combinedStatus;
        $this->pullRequests = array_reduce($this->pullRequests, static function (array $pullRequests, PullRequest $pullRequest): array {
            if ($pullRequest->createdAutomatically()) {
                return $pullRequests;
            }

            $pullRequests[] = $pullRequest;

            return $pullRequests;
        }, []);

        $this->nextTag = DetermineNextReleaseVersion::forTagAndPullRequests(
            $currentTag,
            $pullRequests
        );
    }

    /**
     * @param PullRequest[] $pullRequests
     */
    public static function fromValues(
        Project $project,
        Tag $currentTag,
        CombinedStatus $combinedStatus,
        array $pullRequests
    ): self {
        return new self(
            $project,
            $currentTag,
            $combinedStatus,
            $pullRequests
        );
    }

    public function project(): Project
    {
        return $this->project;
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
            if ($pullRequest->hasChangelog()) {
                return $pullRequests;
            }

            if ($pullRequest->stability()->equals(Stability::pedantic())) {
                return $pullRequests;
            }

            $pullRequests[] = $pullRequest;

            return $pullRequests;
        }, []);
    }

    public function changelog(): Changelog
    {
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

    public function stability(): Stability
    {
        $stabilities = array_map(static function (PullRequest $pr): string {
            return $pr->stability()->toString();
        }, $this->pullRequests);

        if (\in_array(Stability::minor()->toString(), $stabilities, true)) {
            return Stability::minor();
        }

        if (\in_array(Stability::patch()->toString(), $stabilities, true)) {
            return Stability::patch();
        }

        return Stability::pedantic();
    }
}
