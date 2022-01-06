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

namespace App\Github\Domain\Value\PullRequest\Head;

use App\Github\Domain\Value\User;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Repo
{
    private User $owner;

    private function __construct(User $owner)
    {
        $this->owner = $owner;
    }

    /**
     * @param mixed[] $response
     */
    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'owner');

        return new self(
            User::fromResponse($response['owner'])
        );
    }

    public function owner(): User
    {
        return $this->owner;
    }
}
