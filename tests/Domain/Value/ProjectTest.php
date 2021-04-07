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

namespace App\Tests\Domain\Value;

use App\Domain\Value\Project;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class ProjectTest extends TestCase
{
    public const DEFAULT_CONFIG_NAME = 'admin-bundle';
    public const DEFAULT_CONFIG = <<<CONFIG
admin-bundle:
  composer_version: '1'
  phpstan: true
  psalm: true
  panther: true
  excluded_files: []
  custom_gitignore_part: ~
  custom_gitattributes_part: ~
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      tools: []
      php_extensions: []
      docs_path: docs
      tests_path: tests
    3.x:
      php: ['7.2', '7.3', '7.4']
      target_php: ~
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['3']
      tools: []
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG;

    /**
     * @test
     */
    public function valid(): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => $packageName = 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $config = Yaml::parse(self::DEFAULT_CONFIG);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        self::assertSame(self::DEFAULT_CONFIG_NAME, $project->name());
        self::assertSame('SonataAdminBundle', $project->title());
        self::assertSame($packageName, $project->package()->getName());
        self::assertTrue($project->hasBranches());
        self::assertCount(2, $project->branches());
        self::assertSame(['master', '3.x'], $project->branchNames());
        self::assertSame(['3.x', 'master'], $project->branchNamesReverse());
        self::assertSame('master', $project->unstableBranch()->name());
        self::assertSame('3.x', $project->stableBranch()->name());
        self::assertTrue($project->usesPHPStan());
        self::assertTrue($project->usesPsalm());
        self::assertTrue($project->usesPanther());
        self::assertNull($project->customGitignorePart());
        self::assertNull($project->customGitattributesPart());
        self::assertTrue($project->hasDocumentation());
        self::assertSame('1', $project->composerVersion());
        self::assertTrue($project->isBundle());
    }

    /**
     * @test
     *
     * @dataProvider isBundleProvider
     */
    public function isBundle(bool $expected, string $yamlConfig, string $name): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => $packageName = 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $config = Yaml::parse($yamlConfig);

        $project = Project::fromValues(
            $name,
            $config[$name],
            $package
        );

        self::assertSame($expected, $project->isBundle());
    }

    /**
     * @return \Generator<string, array<0: bool, 1: string, 2: string>>
     */
    public function isBundleProvider(): \Generator
    {
        yield 'true - admin-bundle' => [
            true,
$config = <<<CONFIG
admin-bundle:
  composer_version: '1'
  phpstan: true
  psalm: true
  panther: true
  excluded_files: []
  custom_gitignore_part: ~
  custom_gitattributes_part: ~
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      tools: []
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG,
        'admin-bundle',
        ];

        yield 'false - twig-extensions' => [
            false,
$config = <<<CONFIG
twig-extensions:
  composer_version: '1'
  phpstan: true
  psalm: true
  panther: true
  excluded_files: []
  custom_gitignore_part: ~
  custom_gitattributes_part: ~
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      tools: []
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG,
            'twig-extensions',
        ];
    }

    /**
     * @test
     *
     * @dataProvider homepageProvider
     */
    public function homepage(string $expected, string $value): void
    {
        $version = new Package\Version();
        $version->fromArray([
            'homepage' => $value,
        ]);

        $package = new Package();
        $package->fromArray([
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [$version],
        ]);

        $config = Yaml::parse(self::DEFAULT_CONFIG);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        self::assertSame(
            $expected,
            $project->homepage()
        );
    }

    /**
     * @return \Generator<string, array<0: string, 1: string>>
     */
    public function homepageProvider(): \Generator
    {
        yield 'empty string' => [
            'https://sonata-project.org',
            '',
        ];

        yield 'real homepage' => [
            'https://sonata-project.org/bundles/admin',
            'https://sonata-project.org/bundles/admin',
        ];
    }

    /**
     * @test
     *
     * @dataProvider descriptionProvider
     */
    public function description(string $expected, string $value, bool $abandoned): void
    {
        $version = new Package\Version();
        $version->fromArray([
            'description' => $value,
        ]);

        $package = new Package();
        $package->fromArray([
            'abandoned' => $abandoned,
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [$version],
        ]);

        $config = Yaml::parse(self::DEFAULT_CONFIG);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        self::assertSame(
            $expected,
            $project->description()
        );
    }

    /**
     * @return \Generator<string, array<0: string, 1: string, 2: bool>>
     */
    public function descriptionProvider(): \Generator
    {
        yield 'empty string' => [
            '',
            '',
            false,
        ];

        yield 'has description' => [
            'Foo bar',
            'Foo bar',
            false,
        ];

        yield 'has description, but package is abandoned' => [
            '[Abandoned] Foo bar',
            'Foo bar',
            true,
        ];
    }

    /**
     * @test
     */
    public function topics(): void
    {
        $version = new Package\Version();
        $version->fromArray([
            'keywords' => [
                'Admin Generator',
                'orm',
                'Admin',
            ],
        ]);

        $package = new Package();
        $package->fromArray([
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [$version],
        ]);

        $config = Yaml::parse(self::DEFAULT_CONFIG);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        $expected = [
            'admin',
            'admin-generator',
            'bundle',
            'orm',
            'php',
            'sonata',
            'symfony',
            'symfony-bundle',
        ];

        if ('10' === (new \DateTimeImmutable())->format('m')) {
            $expected[] = 'hacktoberfest';

            sort($expected);
        }

        self::assertSame(
            $expected,
            $project->topics()
        );
    }

    /**
     * @test
     */
    public function topicsReturnsDefaultTopicsIfNoTopicsAreSetInPackage(): void
    {
        $version = new Package\Version();
        $version->fromArray([
            'keywords' => [],
        ]);

        $package = new Package();
        $package->fromArray([
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [$version],
        ]);

        $config = Yaml::parse(self::DEFAULT_CONFIG);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        self::assertNotEmpty($project->topics());
    }

    /**
     * @test
     */
    public function defaultTopicsAreNotDuplicatedWithPackageKeywords(): void
    {
        $version = new Package\Version();
        $version->fromArray([
            'keywords' => [
                'Symfony bundle',
                'Sonata',
                'php',
            ],
        ]);

        $package = new Package();
        $package->fromArray([
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [$version],
        ]);

        $config = Yaml::parse(self::DEFAULT_CONFIG);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        $expected = [
            'bundle',
            'php',
            'sonata',
            'symfony',
            'symfony-bundle',
        ];

        if ('10' === (new \DateTimeImmutable())->format('m')) {
            $expected[] = 'hacktoberfest';

            sort($expected);
        }

        self::assertSame(
            $expected,
            $project->topics()
        );
    }
}
