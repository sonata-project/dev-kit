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
    /**
     * @test
     */
    public function valid(): void
    {
        $name = 'admin-bundle';

        $package = new Package();
        $package->fromArray([
            'name' => $packageName = 'sonata-project/admin-bundle',
            'repository' => $repositoryName = 'sonata-project/admin-bundle',
        ]);

        $config = <<<CONFIG
admin-bundle:
  excluded_files: []
  custom_gitignore_part: ~
  custom_doctor_rst_whitelist_part: ~
  docs_target: true
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      variants:
        symfony: ['4.4']
        sonata_block: ['4']
      services: []
      docs_path: docs
      tests_path: tests
    3.x:
      php: ['7.2', '7.3', '7.4']
      target_php: ~
      variants:
        symfony: ['4.4']
        sonata_block: ['3']
      services: []
      docs_path: docs
      tests_path: tests
CONFIG;

        $config = Yaml::parse($config);

        $project = Project::fromValues(
            $name,
            $config['admin-bundle'],
            $package
        );

        self::assertSame($name, $project->name());
        self::assertSame($packageName, $project->package()->getName());
        self::assertTrue($project->hasBranches());
        self::assertCount(2, $project->branches());
        self::assertSame(['master', '3.x'], $project->branchNames());
    }

    /**
     * @return \Generator<array{0: string, 1: string, 2: string}>
     */
    public function validProvider(): \Generator
    {
        yield [
            'sonata-project/dev-kit:"1.*"',
            'sonata-project/dev-kit',
            '1',
        ];

        yield [
            'sonata-project/dev-kit:"1.1.*"',
            'sonata-project/dev-kit',
            '1.1',
        ];

        yield [
            'sonata-project/dev-kit:"dev-master"',
            'sonata-project/dev-kit',
            'dev-master',
        ];
    }
}
