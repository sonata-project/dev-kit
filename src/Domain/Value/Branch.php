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
 *
 * @phpstan-type BranchConfig = array{
 *     php: non-empty-array<string>,
 *     php_extensions: array<string>,
 *     variants: array<string, array<string>>,
 *     target_php: string|null,
 *     frontend: bool,
 *     docs_path: string,
 *     tests_path: string,
 * }
 */
final class Branch
{
    private string $name;

    /**
     * @var non-empty-array<string, PhpVersion>
     */
    private array $phpVersions;

    /**
     * @var PhpExtension[]
     */
    private array $phpExtensions;

    /**
     * @var Variant[]
     */
    private array $variants;

    private bool $frontend;
    private Path $docsPath;
    private Path $testsPath;
    private PhpVersion $targetPhpVersion;

    /**
     * @param non-empty-array<string, PhpVersion> $phpVersions
     * @param PhpExtension[]                      $phpExtensions
     * @param Variant[]                           $variants
     */
    private function __construct(
        string $name,
        array $phpVersions,
        array $phpExtensions,
        array $variants,
        bool $frontend,
        Path $docsPath,
        Path $testsPath,
        ?PhpVersion $targetPhpVersion
    ) {
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
        $this->phpVersions = $phpVersions;
        $this->phpExtensions = $phpExtensions;
        $this->variants = $variants;
        $this->frontend = $frontend;
        $this->docsPath = $docsPath;
        $this->testsPath = $testsPath;
        $this->targetPhpVersion = $targetPhpVersion ?? end($this->phpVersions);
    }

    /**
     * @param mixed[] $config
     *
     * @phpstan-param BranchConfig $config
     */
    public static function fromValues(string $name, array $config): self
    {
        $phpVersions = [];
        foreach ($config['php'] as $version) {
            $phpVersions[$version] = PhpVersion::fromString($version);
        }

        $phpExtensions = array_map(static function (string $phpExtension): PhpExtension {
            return PhpExtension::fromString($phpExtension);
        }, $config['php_extensions']);

        $variants = [];
        foreach ($config['variants'] as $variant => $versions) {
            foreach ($versions as $version) {
                $variants[] = Variant::fromValues($variant, $version);
            }
        }

        $targetPhpVersion = $config['target_php'];
        if (null !== $targetPhpVersion) {
            $targetPhpVersion = PhpVersion::fromString($targetPhpVersion);
        }

        return new self(
            $name,
            $phpVersions,
            $phpExtensions,
            $variants,
            $config['frontend'],
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
     * @return PhpExtension[]
     */
    public function phpExtensions(): array
    {
        return $this->phpExtensions;
    }

    public function hasPhpExtension(string $phpExtensionName): bool
    {
        foreach ($this->phpExtensions() as $phpExtension) {
            if (
                $phpExtension->toString() === $phpExtensionName
                || strstr($phpExtension->toString(), '-', true) === $phpExtensionName
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Variant[]
     */
    public function variants(): array
    {
        return $this->variants;
    }

    public function hasFrontend(): bool
    {
        return $this->frontend;
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
