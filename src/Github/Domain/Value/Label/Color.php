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

namespace App\Github\Domain\Value\Label;

use App\Domain\Value\TrimmedNonEmptyString;
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Color
{
    private string $color;

    private function __construct(string $color)
    {
        $color = TrimmedNonEmptyString::fromString($color)->toString();

        $color = u($color)
            ->lower()
            ->toString();

        Assert::notStartsWith($color, '#');
        Assert::length($color, 6);

        $this->color = $color;
    }

    public static function fromString(string $color): self
    {
        return new self($color);
    }

    public function asHexCode(): string
    {
        return u('#')->append($this->color)->toString();
    }

    public function toString(): string
    {
        return $this->color;
    }

    public function equals(self $other): bool
    {
        return u($this->color)->ignoreCase()->equalsTo($other->toString());
    }
}
