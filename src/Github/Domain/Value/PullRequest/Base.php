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

use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Base
{
    private string $ref;

    private function __construct(string $ref)
    {
        Assert::stringNotEmpty($ref);

        $this->ref = $ref;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'ref');
        Assert::stringNotEmpty($response['ref']);

        return new self($response['ref']);
    }

    public function ref(): string
    {
        return $this->ref;
    }
}
