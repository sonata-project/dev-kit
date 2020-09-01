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

namespace App\Github\Domain\Value\PullRequest;

use App\Github\Domain\Value\PullRequest\Base;
use App\Github\Domain\Value\PullRequest\Head;
use App\Github\Domain\Value\PullRequest\User;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Status
{
    private bool $mergeable;

    private function __construct(bool $mergeable)
    {
        $this->mergeable = $mergeable;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'mergeable');

        return new self($response['mergeable']);
    }

    public function isMergable(): bool
    {
        return $this->mergeable;
    }
}
