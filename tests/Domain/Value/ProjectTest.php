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
use Packagist\Api\Result\Package\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class ProjectTest extends TestCase
{
    public const DEFAULT_CONFIG_NAME = 'admin-bundle';
    public const DEFAULT_CONFIG = <<<CONFIG
admin-bundle:
  phpunit_extensions: ['panther', 'doctrine_test']
  excluded_files: []
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  has_test_kernel: true
  rector: true
  documentation_badge_slug: ~
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
    3.x:
      php: ['7.2', '7.3', '7.4']
      target_php: ~
      frontend: false
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['3']
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

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_CONFIG_NAME, $config);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        static::assertSame(self::DEFAULT_CONFIG_NAME, $project->name());
        static::assertSame('SonataAdminBundle', $project->title());
        static::assertSame('AdminBundle', $project->namespace());
        static::assertSame($packageName, $project->package()->getName());
        static::assertTrue($project->hasBranches());
        static::assertCount(2, $project->branches());
        static::assertSame(['master', '3.x'], $project->branchNames());
        static::assertSame(['3.x', 'master'], $project->branchNamesReverse());
        static::assertSame('master', $project->unstableBranch()->name());
        static::assertNotNull($project->stableBranch());
        static::assertSame('3.x', $project->stableBranch()->name());
        static::assertTrue($project->hasPhpunitExtensions());
        static::assertTrue($project->hasPhpunitExtension('panther'));
        static::assertFalse($project->hasPhpunitExtension('random_extension'));
        static::assertTrue($project->hasDocumentation());
        static::assertTrue($project->hasTestKernel());
        static::assertTrue($project->isBundle());
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
            'name' => 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $config = Yaml::parse($yamlConfig);

        static::assertIsArray($config);
        static::assertArrayHasKey($name, $config);

        $project = Project::fromValues(
            $name,
            $config[$name],
            $package
        );

        static::assertSame($expected, $project->isBundle());
    }

    /**
     * @return iterable<string, array{bool, string, string}>
     */
    public function isBundleProvider(): iterable
    {
        yield 'true - admin-bundle' => [
            true,
<<<CONFIG
admin-bundle:
  phpunit_extensions: ['panther', 'doctrine_test']
  excluded_files: []
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  has_test_kernel: true
  rector: true
  documentation_badge_slug: 'sonataadminbundle2'
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG,
            'admin-bundle',
        ];

        yield 'false - twig-extensions' => [
            false,
<<<CONFIG
twig-extensions:
  phpunit_extensions: ['panther', 'doctrine_test']
  excluded_files: []
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  has_test_kernel: true
  rector: true
  documentation_badge_slug: ~
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
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
        $version = new Version();
        $version->fromArray([
            'homepage' => $value,
        ]);

        $package = new Package();
        $package->fromArray([
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [$version],
        ]);

        $config = Yaml::parse(self::DEFAULT_CONFIG);

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_CONFIG_NAME, $config);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        static::assertSame(
            $expected,
            $project->homepage()
        );
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function homepageProvider(): iterable
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
        $version = new Version();
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

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_CONFIG_NAME, $config);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        static::assertSame(
            $expected,
            $project->description()
        );
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public function descriptionProvider(): iterable
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
        $version = new Version();
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

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_CONFIG_NAME, $config);

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

        static::assertSame(
            $expected,
            $project->topics()
        );
    }

    /**
     * @test
     */
    public function topicsReturnsDefaultTopicsIfNoTopicsAreSetInPackage(): void
    {
        $version = new Version();
        $version->fromArray([
            'keywords' => [],
        ]);

        $package = new Package();
        $package->fromArray([
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [$version],
        ]);

        $config = Yaml::parse(self::DEFAULT_CONFIG);

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_CONFIG_NAME, $config);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        static::assertNotEmpty($project->topics());
    }

    /**
     * @test
     */
    public function defaultTopicsAreNotDuplicatedWithPackageKeywords(): void
    {
        $version = new Version();
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

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_CONFIG_NAME, $config);

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

        static::assertSame(
            $expected,
            $project->topics()
        );
    }

    /**
     * @test
     *
     * @dataProvider documentationBadgeSlugProvider
     */
    public function documentationBadgeSlug(string $expected, string $yamlConfig): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $config = Yaml::parse($yamlConfig);

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_CONFIG_NAME, $config);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        static::assertSame($expected, $project->documentationBadgeSlug());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function documentationBadgeSlugProvider(): iterable
    {
        yield 'null - admin-bundle' => [
            'sonataadminbundle',
<<<CONFIG
admin-bundle:
  phpunit_extensions: ['panther', 'doctrine_test']
  excluded_files: []
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  has_test_kernel: true
  rector: true
  documentation_badge_slug: ~
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG,
        ];

        yield 'custom - admin-bundle' => [
            'sonataadminbundle2',
<<<CONFIG
admin-bundle:
  phpunit_extensions: ['panther', 'doctrine_test']
  excluded_files: []
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  has_test_kernel: true
  rector: true
  documentation_badge_slug: 'sonataadminbundle2'
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG,
        ];
    }

    /**
     * @test
     *
     * @dataProvider defaultBranchProvider
     */
    public function defaultBranch(string $expected, string $yamlConfig): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $config = Yaml::parse($yamlConfig);

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_CONFIG_NAME, $config);

        $project = Project::fromValues(
            self::DEFAULT_CONFIG_NAME,
            $config[self::DEFAULT_CONFIG_NAME],
            $package
        );

        static::assertSame($expected, $project->defaultBranch()->name());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public function defaultBranchProvider(): iterable
    {
        yield 'master' => [
            'master',
            <<<CONFIG
admin-bundle:
  phpunit_extensions: ['panther', 'doctrine_test']
  excluded_files: []
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  has_test_kernel: true
  rector: true
  documentation_badge_slug: ~
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG,
        ];

        yield 'master - 3.x' => [
            '3.x',
            <<<CONFIG
admin-bundle:
  phpunit_extensions: ['panther', 'doctrine_test']
  excluded_files: []
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  has_test_kernel: true
  rector: true
  documentation_badge_slug: ~
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
    3.x:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG,
        ];

        yield 'master - 4.x - 3.x' => [
            '4.x',
            <<<CONFIG
admin-bundle:
  phpunit_extensions: ['panther', 'doctrine_test']
  excluded_files: []
  custom_doctor_rst_whitelist_part: ~
  has_documentation: true
  has_test_kernel: true
  rector: true
  documentation_badge_slug: ~
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
    4.x:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
    3.x:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG,
        ];
    }
}
