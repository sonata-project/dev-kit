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
final class Branch
{
    private string $name;

    /**
     * @var PhpVersion[]
     */
    private array $phpVersions;

    /**
     * @var Service[]
     */
    private array $services;

    private ?PhpVersion $targetPhpVersion;

    /**
     * @param PhpVersion[] $phpVersions
     * @param Service[] $services
     */
    private function __construct(string $name, array $phpVersions, array $services, ?PhpVersion $targetPhpVersion)
    {
        Assert::stringNotEmpty($name);
        $this->name = $name;

        $this->phpVersions = $phpVersions;
        $this->services = $services;
        $this->targetPhpVersion = $targetPhpVersion;
    }

    public static function fromValues(string $name, array $config): self
    {
        $phpVersions = [];
        foreach ($config['php'] as $version) {
            $phpVersions[] = PhpVersion::fromString($version);
        }

        $services = [];
        foreach ($config['services'] as $serviceName) {
            $services[] = Service::fromString($serviceName);
        }

        $targetPhpVersion = $config['target_php'];
        if (null !== $targetPhpVersion) {
            $targetPhpVersion = PhpVersion::fromString($targetPhpVersion);
        }

        return new self(
            $name,
            $phpVersions,
            $services,
            $targetPhpVersion
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return PhpVersion[]
     */
    public function phpVersions(): array
    {
        return $this->phpVersions;
    }

    /**
     * @return Service[]
     */
    public function services(): array
    {
        return $this->services;
    }
}
