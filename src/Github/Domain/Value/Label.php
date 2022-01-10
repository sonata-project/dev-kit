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

use App\Domain\Value\TrimmedNonEmptyString;
use App\Github\Domain\Value\Label\Color;
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Label
{
    private string $name;
    private Color $color;

    private function __construct(string $name, Color $color)
    {
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
        $this->color = $color;
    }

    public static function fromValues(string $name, Color $color): self
    {
        return new self(
            $name,
            $color
        );
    }

    /**
     * @param mixed[] $response
     */
    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'name');
        Assert::keyExists($response, 'color');

        return new self(
            $response['name'],
            Color::fromString($response['color'])
        );
    }

    public static function RTM(): self
    {
        return self::fromValues(
            'RTM',
            Color::fromString('ffffff')
        );
    }

    public static function PendingAuthor(): self
    {
        return self::fromValues(
            'pending author',
            Color::fromString('ededed')
        );
    }

    public function equals(self $other): bool
    {
        return u($this->name)->ignoreCase()->equalsTo($other->name())
            && $this->color->equals($other->color());
    }

    public function color(): Color
    {
        return $this->color;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array{color: string, name: string}
     */
    public function toGithubPayload(): array
    {
        return [
            'color' => $this->color->toString(),
            'name' => $this->name,
        ];
    }
}
