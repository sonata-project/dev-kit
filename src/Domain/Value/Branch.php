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
     * @var array<string>
     */
    private array $phpExtensions;

    /**
     * @var Tool[]
     */
    private array $tools;

    /**
     * @var Variant[]
     */
    private array $variants;

    private Path $docsPath;
    private Path $testsPath;
    private PhpVersion $targetPhpVersion;

    /**
     * @param array<string, PhpVersion> $phpVersions
     * @param Tool[]                    $tools
     * @param string[]                  $phpExtensions
     * @param Variant[]                 $variants
     */
    private function __construct(
        string $name,
        array $phpVersions,
        array $tools,
        array $phpExtensions,
        array $variants,
        Path $docsPath,
        Path $testsPath,
        ?PhpVersion $targetPhpVersion
    ) {
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
        $this->phpVersions = $phpVersions;
        $this->tools = $tools;
        $this->phpExtensions = $phpExtensions;
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

        $tools = array_map(static function (string $toolName): Tool {
            return Tool::fromString($toolName);
        }, $config['tools']);

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
            $tools,
            $config['php_extensions'] ?? [],
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
     * @return Tool[]
     */
    public function tools(): array
    {
        return $this->tools;
    }

    /**
     * @return string[]
     */
    public function phpExtensions(): array
    {
        return $this->phpExtensions;
    }

    public function hasTool(string $toolName): bool
    {
        foreach ($this->tools() as $tool) {
            if ($tool->toString() === $toolName) {
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
