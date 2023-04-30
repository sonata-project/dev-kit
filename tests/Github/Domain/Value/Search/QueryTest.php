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

namespace App\Tests\Github\Domain\Value\Search;

use App\Domain\Value\Branch;
use App\Domain\Value\Repository;
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\Search\Query;
use Ergebnis\Test\Util\Helper;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class QueryTest extends TestCase
{
    use Helper;

    public function testThrowsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Query::fromString('');
    }

    /**
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::lengthGreaterThan256Characters()
     */
    public function testThrowsExceptionIfValueIsGreaterThan256Characters(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Query::fromString($value);
    }

    public function testFromString(): void
    {
        $query = Query::fromString('abc');

        static::assertSame('abc', $query->toString());
    }

    public function testPullRequests(): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => $packageName = 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $repository = Repository::fromPackage($package);

        $config = <<<CONFIG
            master:
              php: ['7.3', '7.4']
              target_php: ~
              frontend: true
              variants:
                symfony/symfony: ['4.4']
                sonata-project/block-bundle: ['4']
              tools: []
              php_extensions: []
              docs_path: docs
              tests_path: tests
            CONFIG;

        $config = Yaml::parse($config);

        static::assertIsArray($config);
        static::assertArrayHasKey('master', $config);

        $branch = Branch::fromValues(
            'master',
            $config['master']
        );

        $query = Query::pullRequests(
            $repository,
            $branch,
            Label::DevKit()
        );

        static::assertSame(
            'repo:sonata-project/SonataAdminBundle type:pr is:merged base:master -label:dev-kit',
            $query->toString()
        );
    }

    public function testPullRequestsSince(): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $repository = Repository::fromPackage($package);

        $config = <<<CONFIG
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
            CONFIG;

        $config = Yaml::parse($config);

        static::assertIsArray($config);
        static::assertArrayHasKey('master', $config);

        $branch = Branch::fromValues(
            'master',
            $config['master']
        );

        $query = Query::pullRequestsSince(
            $repository,
            $branch,
            new \DateTimeImmutable('2020-01-01 10:00:00'),
            Label::DevKit()
        );

        static::assertSame(
            'repo:sonata-project/SonataAdminBundle type:pr is:merged base:master merged:>2020-01-01T10:00:00Z -label:dev-kit',
            $query->toString()
        );
    }
}
