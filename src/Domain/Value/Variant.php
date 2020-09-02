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

use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Variant
{
    private string $name;
    private string $version;


    private function __construct(string $name, string $version)
    {
        Assert::stringNotEmpty($name);
        $this->name = $version;

        Assert::stringNotEmpty($version);
        $this->version = $version;
    }

    public static function fromValues(string $name, string $version): self
    {
        return new self($name, $version);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function toString(): string
    {
        return $this->version;
    }
}
