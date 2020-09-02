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

use Packagist\Api\Result\Package;
use Webmozart\Assert\Assert;
use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Repository
{
    private string $name;
    private string $vendor;
    private string $packageName;

    private function __construct(string $name)
    {
        Assert::stringNotEmpty($name);
        Assert::contains($name, '/');
        $this->name = $name;

        list($vendor, $package) = u($this->name)->replace('.git', '')->split('/');

        $this->vendor = $vendor->toString();
        $this->packageName = $package->toString();
    }

    public static function fromPackage(Package $package): self
    {
        return new self(
            $package->getRepository()
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function vendor(): string
    {
        return $this->vendor;
    }

    public function packageName(): string
    {
        return $this->packageName;
    }
}
