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
final class Branch
{
    private string $name;

    /**
     * @var array<string, PhpVersion>
     */
    private array $phpVersions;

    /**
     * @var Service[]
     */
    private array $services;

    /**
     * @var Variant[]
     */
    private array $variants;

    private Path $docsPath;
    private Path $testsPath;
    private PhpVersion $targetPhpVersion;

    /**
     * @param array<string, PhpVersion> $phpVersions
     * @param Service[]                 $services
     * @param Variant[]                 $variants
     */
    private function __construct(
        string $name,
        array $phpVersions,
        array $services,
        array $variants,
        Path $docsPath,
        Path $testsPath,
        ?PhpVersion $targetPhpVersion
    ) {
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
        $this->phpVersions = $phpVersions;
        $this->services = $services;
        $this->variants = $variants;
        $this->docsPath = $docsPath;
        $this->testsPath = $testsPath;
        $this->targetPhpVersion = $targetPhpVersion ?? end($this->phpVersions);
    }

    public static function fromValues(string $name, array $config): self
    {
        $phpVersions = [];
        foreach ($config['php'] as $version) {
            $phpVersions[$version] = PhpVersion::fromString($version);
        }

        $services = [];
        foreach ($config['services'] as $serviceName) {
            $services[] = Service::fromString($serviceName);
        }

        $variants = [];
        foreach ($config['variants'] as $variant => $versions) {
            foreach ($versions as $version) {
                $variants[] = Variant::fromValues($variant, $version);
            }
        }

        $targetPhpVersion = $config['target_php'];
        if ($targetPhpVersion !== null) {
            $targetPhpVersion = PhpVersion::fromString($targetPhpVersion);
        }

        return new self(
            $name,
            $phpVersions,
            $services,
            $variants,
            Path::fromString($config['docs_path']),
            Path::fromString($config['tests_path']),
            $targetPhpVersion
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, PhpVersion>
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

    public function hasService(string $serviceName): bool
    {
        if ($this->services === []) {
            return false;
        }

        $serviceNames = array_map(static function (Service $service): string {
            return $service->toString();
        }, $this->services());

        return \in_array($serviceName, $serviceNames, true);
    }

    /**
     * @return Variant[]
     */
    public function variants(): array
    {
        return $this->variants;
    }

    public function docsPath(): Path
    {
        return $this->docsPath;
    }

    public function testsPath(): Path
    {
        return $this->testsPath;
    }

    public function highestPhpVersion(): PhpVersion
    {
        return end($this->phpVersions);
    }

    public function lowestPhpVersion(): PhpVersion
    {
        return reset($this->phpVersions);
    }

    public function targetPhpVersion(): PhpVersion
    {
        return $this->targetPhpVersion;
    }
}
