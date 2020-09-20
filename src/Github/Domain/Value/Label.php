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
    private string $name;
    private ?string $color;

    private function __construct(string $name, ?string $color = null)
    {
        $name = trim($name);
        Assert::stringNotEmpty($name);

        if (null !== $color) {
            Assert::stringNotEmpty($color);
            Assert::notStartsWith($color, '#');
        }

        $this->name = $name;
        $this->color = $color;
    }

    public static function fromString(string $name): self
    {
        return new self($name);
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
        return $this->name === $other->name();
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function name(): string
    {
        return $this->name;
    }
}
