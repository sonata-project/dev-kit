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
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
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
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      services: []
      docs_path: docs
      tests_path: tests
    3.x:
      php: ['7.2', '7.3', '7.4']
      target_php: ~
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['3']
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
        self::assertSame('SonataAdminBundle', $project->title());
        self::assertSame($packageName, $project->package()->getName());
        self::assertTrue($project->hasBranches());
        self::assertCount(2, $project->branches());
        self::assertSame(['master', '3.x'], $project->branchNames());
        self::assertSame(['3.x', 'master'], $project->branchNamesReverse());
    }

    /**
     * @test
     */
    public function rawConfig(): void
    {
        $name = 'admin-bundle';

        $package = new Package();
        $package->fromArray([
            'name' => $packageName = 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
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
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
      services: []
      docs_path: docs
      tests_path: tests
    3.x:
      php: ['7.2', '7.3', '7.4']
      target_php: ~
      variants:
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['3']
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

        self::assertSame(
            $config['admin-bundle'],
            $project->rawConfig()
        );
    }

    /**
     * @test
     *
     * @dataProvider homepageProvider
     */
    public function homepage(string $expected, string $value): void
    {
        $name = 'admin-bundle';

        $version = new Package\Version();
        $version->fromArray([
            'homepage' => $value,
        ]);

        $package = new Package();
        $package->fromArray([
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [$version],
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
        symfony/symfony: ['4.4']
        sonata-project/block-bundle: ['4']
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
}
