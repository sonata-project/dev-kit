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
    private string $color;

    private function __construct(string $name, string $color)
    {
        Assert::stringNotEmpty($name, 'Name must not be empty!');
        $this->name = $name;

        Assert::stringNotEmpty($color, 'Color must not be empty!');
        $this->color = $color;
    }

    public static function fromValues(string $name, string $color): self
    {
        return new self($name, $color);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function color(): string
    {
        return $this->color;
    }
}
