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
    private string $url;
    private string $vendor;
    private string $name;

    private function __construct(string $url)
    {
        Assert::stringNotEmpty($url);
        Assert::contains($url, '/');
        Assert::startsWith($url, 'https://github.com/');
        $this->url = $url;

        list($vendor, $name) = u($url)
            ->replace('https://github.com/', '')
            ->replace('.git', '')
            ->split('/');

        $this->vendor = $vendor->toString();
        $this->name = $name->toString();
    }

    public static function fromPackage(Package $package): self
    {
        return new self(
            $package->getRepository()
        );
    }

    public function url(): string
    {
        return $this->url;
    }

    public function vendor(): string
    {
        return $this->vendor;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function toString(): string
    {
        return u($this->vendor)
            ->append('/')
            ->append($this->name)
            ->toString();
    }
}
