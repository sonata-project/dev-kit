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

use App\Domain\Value\Branch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class BranchTest extends TestCase
{
    public const string DEFAULT_BRANCH_NAME = 'master';
    public const string DEFAULT_BRANCH_CONFIG = <<<CONFIG
        master:
          php: ['7.3', '7.4', '8.0']
          target_php: '7.4'
          frontend: true
          frontend_tests: true
          variants:
            symfony/symfony: ['4.4']
            sonata-project/block-bundle: ['4']
          php_extensions: ['bar']
          docs_path: docs
          tests_path: tests
        CONFIG;

    public function testValid(): void
    {
        $name = 'master';

        $config = Yaml::parse(self::DEFAULT_BRANCH_CONFIG);

        static::assertIsArray($config);
        static::assertArrayHasKey(self::DEFAULT_BRANCH_NAME, $config);

        $branch = Branch::fromValues(
            $name,
            $config[self::DEFAULT_BRANCH_NAME]
        );

        static::assertSame($name, $branch->name());
        static::assertCount(3, $branch->phpVersions());
        static::assertSame('7.4', $branch->targetPhpVersion()->toString());
        static::assertSame('7.3', $branch->lowestPhpVersion()->toString());
        static::assertSame('8.0', $branch->highestPhpVersion()->toString());
        static::assertCount(2, $branch->variants());
        static::assertSame('docs', $branch->docsPath()->toString());
        static::assertSame('tests', $branch->testsPath()->toString());
        static::assertTrue($branch->hasFrontend());
        static::assertTrue($branch->hasPhpExtension('bar'));
    }

    /**
     * @param string[] $phpExtensions
     */
    #[DataProvider('provideHasPhpExtensionCases')]
    public function testHasPhpExtension(bool $expected, array $phpExtensions, string $phpExtensionName): void
    {
        $branch = self::createBranch(phpExtensions: $phpExtensions);

        static::assertSame($expected, $branch->hasPhpExtension($phpExtensionName));
    }

    /**
     * @return iterable<string, array{bool, string[], string}>
     */
    public static function provideHasPhpExtensionCases(): iterable
    {
        yield 'no extensions' => [false, [], 'mongodb'];
        yield 'exact match' => [true, ['mongodb'], 'mongodb'];
        yield 'exact match among others' => [true, ['redis', 'mongodb', 'intl'], 'mongodb'];
        yield 'match with version suffix' => [true, ['mongodb-1.17.0'], 'mongodb'];
        yield 'match with version suffix among others' => [true, ['redis', 'mongodb-1.17.0'], 'mongodb'];
        yield 'no match' => [false, ['redis', 'intl'], 'mongodb'];
        yield 'prefix without dash does not match' => [false, ['mongodbx'], 'mongodb'];
        yield 'suffixed search name does not match' => [false, ['mongodb'], 'mongodb-1.17.0'];
        yield 'only part before first dash is compared' => [false, ['mongo-db-1.0'], 'mongo-db'];
    }

    /**
     * @param array<string, string[]> $variants
     */
    #[DataProvider('provideHasVariantCases')]
    public function testHasVariant(bool $expected, array $variants, string $package, string $version): void
    {
        $branch = self::createBranch(variants: $variants);

        static::assertSame($expected, $branch->hasVariant($package, $version));
    }

    /**
     * @return iterable<string, array{bool, array<string, string[]>, string, string}>
     */
    public static function provideHasVariantCases(): iterable
    {
        $variants = [
            'symfony/symfony' => ['5.4', '6.4'],
            'sonata-project/block-bundle' => ['4'],
        ];

        yield 'no variants' => [false, [], 'symfony/symfony', '5.4'];
        yield 'match' => [true, $variants, 'symfony/symfony', '5.4'];
        yield 'match second version' => [true, $variants, 'symfony/symfony', '6.4'];
        yield 'match second package' => [true, $variants, 'sonata-project/block-bundle', '4'];
        yield 'package matches, version does not' => [false, $variants, 'symfony/symfony', '7.0'];
        yield 'version matches, package does not' => [false, $variants, 'symfony/form', '5.4'];
        yield 'version of other package does not match' => [false, $variants, 'sonata-project/block-bundle', '5.4'];
    }

    /**
     * @param string[]                $phpExtensions
     * @param array<string, string[]> $variants
     */
    private static function createBranch(array $phpExtensions = [], array $variants = []): Branch
    {
        return Branch::fromValues(self::DEFAULT_BRANCH_NAME, [
            'php' => ['8.3', '8.4'],
            'target_php' => null,
            'frontend' => false,
            'frontend_tests' => false,
            'variants' => $variants,
            'php_extensions' => $phpExtensions,
            'docs_path' => 'docs',
            'tests_path' => 'tests',
        ]);
    }
}
