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

use App\Github\Domain\Value\CombinedStatus;
use App\Github\Domain\Value\PullRequest;
use App\Github\Domain\Value\Release\Tag;
use Packagist\Api\Result\Package;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class NextRelease
{
    private Package $package;
    private Tag $currentTag;
    private Tag $nextTag;
    private CombinedStatus $combinedStatus;

    /**
     * @var PullRequest[]
     */
    private array $pullRequests;

    private function __construct(
        Package $package,
        Tag $currentTag,
        Tag $nextTag,
        CombinedStatus $combinedStatus,
        array $pullRequests
    ) {
        $this->package = $package;
        $this->currentTag = $currentTag;
        $this->nextTag = $nextTag;
        $this->combinedStatus = $combinedStatus;
        $this->pullRequests = $pullRequests;
    }

    /**
     * @param PullRequest[] $pullRequests
     */
    public static function fromValues(
        Package $package,
        Tag $currentTag,
        Tag $nextTag,
        CombinedStatus $combinedStatus,
        array $pullRequests
    ): self {
        return new self(
            $package,
            $currentTag,
            $nextTag,
            $combinedStatus,
            $pullRequests
        );
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

    public function changelog(): Changelog
    {
        return Changelog::fromPullRequests(
            $this->pullRequests,
            $this->nextTag,
            $this->currentTag,
            $this->package
        );
    }

    public function isNeeded(): bool
    {
        return $this->nextTag->toString() !== $this->currentTag->toString();
    }
}
