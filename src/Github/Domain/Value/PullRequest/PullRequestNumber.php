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
 *
 * "Number" would be enough, but is a reserved class name in PHP! Oskar
 */
final class PullRequestNumber
{
    private int $number;

    private function __construct(int $number)
    {
        Assert::greaterThan($number, 0);

        $this->number = $number;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'number');

        return new self($response['number']);
    }

    public static function fromInt(int $number): self
    {
        return new self($number);
    }

    public function toInt(): int
    {
        return $this->number;
    }
}
