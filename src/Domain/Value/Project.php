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
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Project
{
    private array $rawConfig;
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

        $project = new self(
            $name,
            $package,
            $branches,
            $excludedFiles,
            $config['docs_target'],
            $config['custom_gitignore_part'],
            $config['custom_doctor_rst_whitelist_part'],
        );
        $project->rawConfig = $config;

        return $project;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function title(): string
    {
        return u($this->package->getName())
            ->replace('-project', '')
            ->replace('/', ' ')
            ->replace('-', ' ')
            ->title(true)
            ->replace(' ', '')
            ->toString();
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
     * @return Branch[]
     */
    public function branchesReverse(): array
    {
        return array_reverse($this->branches);
    }

    /**
     * @return string[]
     */
    public function branchNames(): array
    {
        return array_map(static function (Branch $branch): string {
            return $branch->name();
        }, $this->branches);
    }

    /**
     * @return string[]
     */
    public function branchNamesReverse(): array
    {
        return array_reverse($this->branchNames());
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

    public function hasBranches(): bool
    {
        return [] !== $this->branches;
    }

    public function websitePath(): string
    {
        return u($this->package->getName())
            ->replace('sonata-project/', '')
            ->replace('-bundle', '')
            ->toString();
    }

    public function homepage(): string
    {
        $latestVersion = $this->getLatestPackagistVersion();

        return $latestVersion->getHomepage() ?: 'https://sonata-project.org';
    }

    public function description(): string
    {
        $latestVersion = $this->getLatestPackagistVersion();

        return $latestVersion->isAbandoned()
            ? '[Abandonned] '.$latestVersion->getDescription()
            : $latestVersion->getDescription();
    }

    /**
     * @return string[]
     */
    public function topics(): array
    {
        $latestVersion = $this->getLatestPackagistVersion();

        $keywords = $latestVersion->getKeywords();
        \assert(is_array($keywords));

        sort($keywords);

        return array_map(static function(string $keyword): string {
            return u($keyword)->lower()->replace(' ', '-')->toString();
        }, $keywords);
    }

    /**
     * We keep this method to have a smooth transition and
     * remove it when we did not use config arrays anymore. Oskar.
     *
     * @return array<mixed>
     */
    public function rawConfig(): array
    {
        return $this->rawConfig;
    }

    private function getLatestPackagistVersion(): Package\Version
    {
        $versions = $this->package->getVersions();
        $latest = reset($versions);

        if (false === $latest) {
            $latest = new Package\Version();
        }

        return $latest;
    }
}
