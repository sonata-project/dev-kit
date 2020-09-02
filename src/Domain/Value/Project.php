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

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Project
{
    private string $name;
    private Package $package;

    /**
     * @var Branch[]
     */
    private array $branches;

    /**
     * @var ExcludedFile[]
     */
    private array $excludedFiles;

    private bool $docsTarget;
    private ?string $customGitignorePart;
    private ?string $customDoctorRstWhitelistPart;

    private Repository $repository;

    private function __construct(
        string $name,
        Package $package,
        array $branches,
        array $excludedFiles,
        bool $docsTarget,
        ?string $customGitignorePart,
        ?string $customDoctorRstWhitelistPart
    ) {
        Assert::stringNotEmpty($name);
        $this->name = $name;

        $this->package = $package;
        $this->branches = $branches;
        $this->docsTarget = $docsTarget;
        $this->excludedFiles = $excludedFiles;
        $this->customGitignorePart = $customGitignorePart;
        $this->customDoctorRstWhitelistPart = $customDoctorRstWhitelistPart;

        $this->repository = Repository::fromPackage($package);
    }

    public static function fromValues(string $name, array $config, Package $package): self
    {
        $branches = [];
        foreach ($config['branches'] as $branchName => $branchConfig) {
            $branches[] = Branch::fromValues($branchName, $branchConfig);
        }

        $excludedFiles = [];
        foreach ($config['excluded_files'] as $filename) {
            $excludedFiles[] = ExcludedFile::fromString($filename);
        }

        return new self(
            $name,
            $package,
            $branches,
            $excludedFiles,
            $config['docs_target'],
            $config['custom_gitignore_part'],
            $config['custom_doctor_rst_whitelist_part'],

        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function package(): Package
    {
        return $this->package;
    }

    /**
     * @return Branch[]
     */
    public function branches(): array
    {
        return $this->branches;
    }

    /**
     * @return ExcludedFile[]
     */
    public function excludedFiles(): array
    {
        return $this->excludedFiles;
    }

    public function docsTarget(): bool
    {
        return $this->docsTarget;
    }

    public function customGitignorePart(): ?string
    {
        return $this->customGitignorePart;
    }

    public function customDoctorRstWhitelistPart(): ?string
    {
        return $this->customDoctorRstWhitelistPart;
    }

    public function repository(): Repository
    {
        return $this->repository;
    }
}
