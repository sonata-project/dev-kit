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

use App\Github\Domain\Value\PullRequest\Head\Repo\Owner;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Repo
{
    private Owner $owner;

    private function __construct(Owner $owner)
    {
        $this->owner = $owner;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'owner');
        Assert::notEmpty($response['owner']);

        return new self(
            Owner::fromResponse($response['owner'])
        );
    }

    public function owner(): Owner
    {
        return $this->owner;
    }
}
