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

namespace App\Github\Domain\Value;

use App\Github\Domain\Value\PullRequest\Base;
use App\Github\Domain\Value\PullRequest\Head;
use App\Github\Domain\Value\PullRequest\User;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class PullRequest
{
    private Issue $issue;
    private string $title;
    private \DateTimeImmutable $updatedAt;
    private Base $base;
    private Head $head;
    private User $user;
    private ?bool $mergeable;

    private function __construct(Issue $issue, string $title, string $updatedAt, Base $base, Head $head, User $user, ?bool $mergeable = null)
    {
        Assert::stringNotEmpty($title);
        Assert::stringNotEmpty($updatedAt);

        $this->issue = $issue;
        $this->title = $title;
        $this->updatedAt = new \DateTimeImmutable(
            $updatedAt,
            new \DateTimeZone('UTC')
        );
        $this->base = $base;
        $this->head = $head;
        $this->user = $user;
        $this->mergeable = $mergeable;
    }

    public static function fromDetailResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'number');

        Assert::keyExists($response, 'title');
        Assert::stringNotEmpty($response['title']);

        Assert::keyExists($response, 'updated_at');
        Assert::stringNotEmpty($response['updated_at']);

        Assert::keyExists($response, 'base');
        Assert::notEmpty($response['base']);

        Assert::keyExists($response, 'head');
        Assert::notEmpty($response['head']);

        Assert::keyExists($response, 'user');
        Assert::notEmpty($response['user']);

        Assert::keyExists($response, 'mergeable');
        Assert::nullOrBoolean($response['mergeable']);

        return new self(
            Issue::fromInt($response['number']),
            $response['title'],
            $response['updated_at'],
            Base::fromResponse($response['base']),
            Head::fromResponse($response['head']),
            User::fromResponse($response['user']),
            $response['mergeable']
        );
    }

    public function issue(): Issue
    {
        return $this->issue;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function base(): Base
    {
        return $this->base;
    }

    public function head(): Head
    {
        return $this->head;
    }

    public function user(): User
    {
        return $this->user;
    }

    /**
     * The value of the mergeable attribute can be true, false, or null.
     * If the value is null this means that the mergeability hasn't been computed yet.
     *
     * @see: https://developer.github.com/v3/pulls/#get-a-single-pull-request
     */
    public function isMergeable(): ?bool
    {
        return $this->mergeable;
    }

    public function updatedWithinTheLast60Seconds(): bool
    {
        $diff = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp()
            - $this->updatedAt->getTimestamp();

        return  $diff < 60;
    }
}
