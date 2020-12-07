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

use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Stability
{
    private string $value;

    private function __construct(string $value)
    {
        $value = TrimmedNonEmptyString::fromString($value)->toString();

        $value = u($value)->lower()->toString();

        Assert::oneOf(
            $value,
            [
                'minor',
                'patch',
                'pedantic',
                'unknown',
            ]
        );

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function minor(): self
    {
        return new self('minor');
    }

    public static function patch(): self
    {
        return new self('patch');
    }

    public static function pedantic(): self
    {
        return new self('pedantic');
    }

    public static function unknown(): self
    {
        return new self('unknown');
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toUppercaseString(): string
    {
        return u($this->value)->upper()->toString();
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->toString();
    }

    public function notEquals(self $other): bool
    {
        return !$this->equals($other);
    }
}
