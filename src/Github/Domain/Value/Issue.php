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

use Webmozart\Assert\Assert;

use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Issue
{
    private int $value;

    private function __construct(int $value)
    {
        Assert::greaterThan($value, 0);

        $this->value = $value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return u('#')->append((string) $this->value)->toString();
    }

    public function toInt(): int
    {
        return $this->value;
    }
}
