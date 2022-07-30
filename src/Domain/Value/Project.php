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
use Packagist\Api\Result\Package\Version;

use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 *
 * @phpstan-import-type BranchConfig from Branch
 *
 * @phpstan-type ProjectConfig = array{
 *     branches: array<string, BranchConfig>,
 *     excluded_files: array<string>,
 *     phpunit_extensions: array<string>,
 *     has_documentation: bool,
 *     has_test_kernel: bool,
 *     custom_doctor_rst_whitelist_part: string|null,
 *     documentation_badge_slug: string|null,
 * }
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

    /**
     * @var PhpunitExtension[]
     */
    private array $phpunitExtensions;

    private bool $hasDocumentation;
    private bool $hasTestKernel;
    private ?string $customDoctorRstWhitelistPart;
    private string $documentationBadgeSlug;

    private Repository $repository;

    /**
     * @param Branch[]           $branches
     * @param ExcludedFile[]     $excludedFiles
     * @param PhpunitExtension[] $phpunitExtensions
     */
    private function __construct(
        string $name,
        Package $package,
        array $branches,
        array $excludedFiles,
        array $phpunitExtensions,
        bool $hasDocumentation,
        bool $hasTestKernel,
        ?string $customDoctorRstWhitelistPart,
        ?string $documentationBadgeSlug
    ) {
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
        $this->bundle = u($this->name)->endsWith('bundle') ? true : false;
        $this->repository = Repository::fromPackage($package);

        $this->package = $package;
        $this->branches = $branches;
        $this->hasDocumentation = $hasDocumentation;
        $this->hasTestKernel = $hasTestKernel;
        $this->excludedFiles = $excludedFiles;
        $this->phpunitExtensions = $phpunitExtensions;
        $this->customDoctorRstWhitelistPart = $customDoctorRstWhitelistPart;
        $this->documentationBadgeSlug = $documentationBadgeSlug ?? u($this->repository->name())
            ->lower()
            ->toString();
    }

    /**
     * @param mixed[] $config
     *
     * @phpstan-param ProjectConfig $config
     */
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

        $phpunitExtensions = [];
        foreach ($config['phpunit_extensions'] as $phpunitExtension) {
            $phpunitExtensions[] = PhpunitExtension::fromString($phpunitExtension);
        }

        return new self(
            $name,
            $package,
            $branches,
            $excludedFiles,
            $phpunitExtensions,
            $config['has_documentation'],
            $config['has_test_kernel'],
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
            ->replace(' Orm ', ' ORM ')
            ->replace('db ', 'DB ')
            ->replace(' ', '')
            ->toString();
    }

    public function namespace(): string
    {
        return u($this->title())
            ->replace('Sonata', '')
            ->replace('Extensions', '')
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
        return array_map(
            static fn (Branch $branch): string => $branch->name(),
            $this->branches
        );
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

    public function hasPhpunitExtension(string $extension): bool
    {
        foreach ($this->phpunitExtensions as $phpunitExtension) {
            if ($phpunitExtension->extension() === $extension) {
                return true;
            }
        }

        return false;
    }

    public function hasPhpunitExtensions(): bool
    {
        return [] !== $this->phpunitExtensions;
    }

    public function hasDocumentation(): bool
    {
        return $this->hasDocumentation;
    }

    public function hasTestKernel(): bool
    {
        return $this->hasTestKernel;
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
        if ('' !== $latestVersion->getHomepage()) {
            return $latestVersion->getHomepage();
        }

        return 'https://sonata-project.org';
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

        $keywords = array_map(
            static fn (string $keyword): string => u($keyword)
                ->lower()
                ->replace(' ', '-')
                ->trim()
                ->toString(),
            array_merge($default, $latestVersion->getKeywords())
        );

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

    private function getLatestPackagistVersion(): Version
    {
        $versions = $this->package->getVersions();
        $latest = reset($versions);

        if (false === $latest) {
            return new Version();
        }

        return $latest;
    }
}
