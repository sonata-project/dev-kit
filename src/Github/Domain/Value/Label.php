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

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Label
{
    private string $value;
    private ?string $color;

    private function __construct(string $value, ?string $color = null)
    {
        $value = trim($value);
        Assert::stringNotEmpty($value);

        if (null !== $color) {
            Assert::stringNotEmpty($color);
        }

        $this->value = $value;
        $this->color = $color;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    /**
     * @param array{name: string, color: string} $response
     */
    public static function fromResponse(array $response): self
    {
        Assert::keyExists($response, 'name');
        Assert::keyExists($response, 'color');

        return new self(
            $response['name'],
            $response['color']
        );
    }

    public static function RTM(): self
    {
        return self::fromString('RTM');
    }

    public static function PendingAuthor(): self
    {
        return self::fromString('pending author');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->toString();
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
