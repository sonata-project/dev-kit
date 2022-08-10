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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class BranchTest extends TestCase
{
    public const DEFAULT_BRANCH_NAME = 'master';
    public const DEFAULT_BRANCH_CONFIG = <<<CONFIG
        master:
          php: ['7.3', '7.4', '8.0']
          target_php: '7.4'
          frontend: true
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
}
