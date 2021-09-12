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

use App\Config\Exception\UnknownBranch;
use App\Domain\Exception\NoBranchesAvailable;
use Packagist\Api\Result\Package;
use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Project
{
    private string $name;
    private bool $bundle;
    private Package $package;

    /**
     * @var Branch[]
     */
    private array $branches;

    /**
     * @var ExcludedFile[]
     */
    private array $excludedFiles;

    private string $composerVersion;
    private bool $hasDocumentation;
    private bool $phpstan;
    private bool $psalm;
    private bool $panther;
    private ?string $customGitignorePart;
    private ?string $customGitattributesPart;
    private ?string $customDoctorRstWhitelistPart;
    private string $documentationBadgeSlug;

    private Repository $repository;

    private function __construct(
        string $name,
        Package $package,
        array $branches,
        array $excludedFiles,
        string $composerVersion,
        bool $hasDocumentation,
        bool $phpstan,
        bool $psalm,
        bool $panther,
        ?string $customGitignorePart,
        ?string $customGitattributesPart,
        ?string $customDoctorRstWhitelistPart,
        ?string $documentationBadgeSlug
    ) {
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
        $this->bundle = u($this->name)->endsWith('bundle') ? true : false;
        $this->repository = Repository::fromPackage($package);

        $this->package = $package;
        $this->branches = $branches;
        $this->composerVersion = $composerVersion;
        $this->hasDocumentation = $hasDocumentation;
        $this->phpstan = $phpstan;
        $this->psalm = $psalm;
        $this->panther = $panther;
        $this->excludedFiles = $excludedFiles;
        $this->customGitignorePart = $customGitignorePart;
        $this->customGitattributesPart = $customGitattributesPart;
        $this->customDoctorRstWhitelistPart = $customDoctorRstWhitelistPart;
        $this->documentationBadgeSlug = $documentationBadgeSlug ?? u($this->repository->name())
            ->lower()
            ->toString();
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
            $config['composer_version'],
            $config['has_documentation'],
            $config['phpstan'],
            $config['psalm'],
            $config['panther'],
            $config['custom_gitignore_part'],
            $config['custom_gitattributes_part'],
            $config['custom_doctor_rst_whitelist_part'],
            $config['documentation_badge_slug'],
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isBundle(): bool
    {
        return $this->bundle;
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

    public function branch(string $name): Branch
    {
        foreach ($this->branches as $branch) {
            if ($branch->name() === $name) {
                return $branch;
            }
        }

        throw UnknownBranch::forName($this, $name);
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

    public function composerVersion(): string
    {
        return $this->composerVersion;
    }

    public function hasDocumentation(): bool
    {
        return $this->hasDocumentation;
    }

    public function usesPHPStan(): bool
    {
        return $this->phpstan;
    }

    public function usesPsalm(): bool
    {
        return $this->psalm;
    }

    public function usesPanther(): bool
    {
        return $this->panther;
    }

    public function customGitignorePart(): ?string
    {
        return $this->customGitignorePart;
    }

    public function customGitattributesPart(): ?string
    {
        return $this->customGitattributesPart;
    }

    public function customDoctorRstWhitelistPart(): ?string
    {
        return $this->customDoctorRstWhitelistPart;
    }

    public function documentationBadgeSlug(): string
    {
        return $this->documentationBadgeSlug;
    }

    public function repository(): Repository
    {
        return $this->repository;
    }

    public function hasBranches(): bool
    {
        return [] !== $this->branches;
    }

    public function homepage(): string
    {
        $latestVersion = $this->getLatestPackagistVersion();

        return $latestVersion->getHomepage() ?: 'https://sonata-project.org';
    }

    public function description(): string
    {
        $latestVersion = $this->getLatestPackagistVersion();

        return $this->package->isAbandoned()
            ? '[Abandoned] '.$latestVersion->getDescription()
            : $latestVersion->getDescription();
    }

    /**
     * @return string[]
     */
    public function topics(): array
    {
        $default = [
            'PHP',
            'Sonata',
            'Symfony',
        ];

        if (u($this->name)->endsWith('bundle')) {
            $default[] = 'Bundle';
            $default[] = 'Symfony-Bundle';
        }

        /*
         * add "Hacktoberfest" topic to repositories in october
         */
        if ('10' === (new \DateTimeImmutable())->format('m')) {
            $default[] = 'Hacktoberfest';
        }

        $latestVersion = $this->getLatestPackagistVersion();

        $keywords = array_map(static function (string $keyword): string {
            return u($keyword)
                ->lower()
                ->replace(' ', '-')
                ->trim()
                ->toString();
        }, array_merge($default, $latestVersion->getKeywords()));

        sort($keywords);

        return array_values(array_unique($keywords));
    }

    public function unstableBranch(): Branch
    {
        if (!$this->hasBranches()) {
            throw NoBranchesAvailable::forProject($this);
        }

        return $this->branches[0];
    }

    public function stableBranch(): ?Branch
    {
        if (!$this->hasBranches()) {
            throw NoBranchesAvailable::forProject($this);
        }

        return $this->branches[1] ?? null;
    }

    public function defaultBranch(): Branch
    {
        if (!$this->hasBranches()) {
            throw NoBranchesAvailable::forProject($this);
        }

        return $this->stableBranch() ?? $this->unstableBranch();
    }

    public function isStable(): bool
    {
        return null !== $this->stableBranch();
    }

    private function getLatestPackagistVersion(): Package\Version
    {
        $versions = $this->package->getVersions();
        $latest = reset($versions);

        if (false === $latest) {
            return new Package\Version();
        }

        return $latest;
    }
}
