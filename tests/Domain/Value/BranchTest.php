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
  variants:
    symfony/symfony: ['4.4']
    sonata-project/block-bundle: ['4']
  tools: ['foo']
  php_extensions: ['bar']
  docs_path: docs
  tests_path: tests
CONFIG;

    /**
     * @test
     */
    public function valid(): void
    {
        $name = 'master';

        $config = Yaml::parse(self::DEFAULT_BRANCH_CONFIG);

        $branch = Branch::fromValues(
            $name,
            $config[self::DEFAULT_BRANCH_NAME]
        );

        self::assertSame($name, $branch->name());
        self::assertCount(3, $branch->phpVersions());
        self::assertSame('7.4', $branch->targetPhpVersion()->toString());
        self::assertSame('7.3', $branch->lowestPhpVersion()->toString());
        self::assertSame('8.0', $branch->highestPhpVersion()->toString());
        self::assertCount(2, $branch->variants());
        self::assertSame('docs', $branch->docsPath()->toString());
        self::assertSame('tests', $branch->testsPath()->toString());
        self::assertTrue($branch->hasTool('foo'));
        self::assertTrue($branch->hasPhpExtension('bar'));
    }
}
