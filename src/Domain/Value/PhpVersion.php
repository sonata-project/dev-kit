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
final class PhpVersion
{
    private string $version;

    private function __construct(string $version)
    {
        Assert::stringNotEmpty($version);
        $this->version = $version;
    }

    public static function fromString(string $version): self
    {
        return new self($version);
    }

    public function version(): string
    {
        return $this->version;
    }
}
