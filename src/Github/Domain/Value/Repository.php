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
final class Repository
{
    private string $vendor;
    private string $package;

    private function __construct(string $vendor, string $package)
    {
        Assert::stringNotEmpty($vendor);
        Assert::stringNotEmpty($package);

        $this->vendor = $vendor;
        $this->package = $package;
    }

    public static function fromString(string $repository): self
    {
        Assert::stringNotEmpty($repository);
        Assert::contains($repository, '/');

        [$vendor, $package] = u($repository)->split('/');

        return new self(
            $vendor->toString(),
            $package->toString()
        );
    }

    public function vendor(): string
    {
        return $this->vendor;
    }

    public function package(): string
    {
        return $this->package;
    }

    public function toString(): string
    {
        return sprintf(
            '%s/%s',
            $this->vendor,
            $this->package
        );
    }
}
