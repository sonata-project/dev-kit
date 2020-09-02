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

    /**
     * @var Variant[]
     */
    private array $variants;

    private Path $docsPath;
    private Path $testsPath;
    private ?PhpVersion $targetPhpVersion;

    /**
     * @param PhpVersion[] $phpVersions
     * @param Service[] $services
     * @param Variant[] $variants
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
        Assert::stringNotEmpty($name);
        $this->name = $name;

        $this->phpVersions = $phpVersions;
        $this->services = $services;
        $this->variants = $variants;
        $this->docsPath = $docsPath;
        $this->testsPath = $testsPath;
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

        $variants = [];
        foreach ($config['variants'] as $variant => $verrsion) {
            $variants[] = Variant::fromValues($variant, $version);
        }

        $targetPhpVersion = $config['target_php'];
        if (null !== $targetPhpVersion) {
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

    public function docsPath(): Path
    {
        return $this->docsPath;
    }

    public function testsPath(): Path
    {
        return $this->testsPath;
    }

    public function targetPhpVersion(): ?PhpVersion
    {
        return $this->targetPhpVersion;
    }
}
