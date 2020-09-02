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
    private string $package;
    private string $version;

    private function __construct(string $package, string $version)
    {
        Assert::stringNotEmpty($package, 'Package must not be empty!');
        Assert::contains($package, 'Package must contain a "/"!');
        $this->package = $package;

        Assert::stringNotEmpty($version, 'Version must not be empty!');
        $this->version = $version;
    }

    public static function fromValues(string $package, string $version): self
    {
        return new self($package, $version);
    }

    public function package(): string
    {
        return $this->package;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function toString(): string
    {
        return sprintf(
            '%s:"%s"',
            $this->package,
            'dev-master' === $this->version ? $this->version : ($$this->version.'.*')
        );
    }
}
