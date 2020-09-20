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

use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Label
{
    private string $name;
    private string $color;

    private function __construct(string $name, string $color)
    {
        $name = trim($name);
        Assert::stringNotEmpty($name);

        $color = u($color)->trim()->lower()->toString();
        Assert::stringNotEmpty($color);
        Assert::notStartsWith($color, '#');
        Assert::length($color, 6);

        $this->name = $name;
        $this->color = $color;
    }

    public static function fromValues(string $name, string $color): self
    {
        return new self(
            $name,
            $color
        );
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
        return self::fromValues(
            'RTM',
            'ffffff'
        );
    }

    public static function PendingAuthor(): self
    {
        return self::fromValues(
            'pending author',
            'ededed'
        );
    }

    public function equals(self $other): bool
    {
        return u($this->name)->ignoreCase()->equalsTo($other->name())
            && u($this->color)->ignoreCase()->equalsTo($other->color)
            ;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function colorWithLeadingHash(): string
    {
        return u('#')->append($this->color)->toString();
    }

    public function name(): string
    {
        return $this->name;
    }
}
