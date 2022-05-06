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

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class PhpVersion
{
    private string $version;

    private function __construct(string $version)
    {
        $this->version = TrimmedNonEmptyString::fromString($version)->toString();
    }

    public static function fromString(string $version): self
    {
        return new self($version);
    }

    public function rectorFormat(): string
    {
        return str_replace('.', '', $this->version);
    }

    public function toString(): string
    {
        return $this->version;
    }
}
