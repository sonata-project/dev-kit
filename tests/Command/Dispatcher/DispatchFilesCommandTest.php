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

namespace App\Tests\Command\Dispatcher;

use App\Domain\Value\Project;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class DispatchFilesCommandTest extends TestCase
{
    private Environment $environment;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader([
            __DIR__.'/../../../templates/project/.github/workflows',
        ]);

        $this->environment = new Environment($loader, [
            'strict_variables' => true,
            'cache' => false,
            'autoescape' => 'html',
            'optimizations' => 0,
        ]);
    }

    /**
     * @dataProvider projectProvider
     */
    public function testTestFileRendering(string $expected, string $project): void
    {
        $config = Yaml::parse($project);

        $package = new Package();
        $package->fromArray([
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
            'versions' => [],
        ]);

        $project = Project::fromValues(
            'admin-bundle',
            $config['admin-bundle'],
            $package
        );

        $result = $this->environment->render('test.yaml.twig', [
            'project' => $project,
            'branch' => $project->branches()[0],
        ]);

        static::assertSame($expected, $result);
    }

    /**
     * @return \Generator<string, array<0: string, 1: string>>
     */
    public function projectProvider(): \Generator
    {
        $project = <<<CONFIG
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
  documentation_badge_slug: ~
  branches:
    master:
      php: ['7.3', '7.4']
      target_php: ~
      frontend: true
      custom_gitignore_part: ~
      variants:
        symfony/symfony: ['4.4.*']
        sonata-project/block-bundle: ['4.*']
      tools: []
      php_extensions: []
      docs_path: docs
      tests_path: tests
    3.x:
      php: ['7.2', '7.3', '7.4']
      target_php: ~
      frontend: false
      custom_gitignore_part: ~
      variants:
        symfony/symfony: ['4.4.*']
        sonata-project/block-bundle: ['3.*']
      tools: []
      php_extensions: []
      docs_path: docs
      tests_path: tests
CONFIG;

        $result = <<<'OUTPUT'
# DO NOT EDIT THIS FILE!
#
# It's auto-generated by sonata-project/dev-kit package.

name: Test

on:
    push:
        branches:
            - 3.x
            - master
    pull_request:

jobs:
    test:
        name: PHP ${{ matrix.php-version }} + ${{ matrix.dependencies }} + ${{ matrix.variant }}

        runs-on: ubuntu-latest

        continue-on-error: ${{ matrix.allowed-to-fail }}

        env:
            SYMFONY_REQUIRE: ${{matrix.symfony-require}}

        strategy:
            matrix:
                php-version:
                    - '7.3'
                    - '7.4'
                dependencies: [highest]
                allowed-to-fail: [false]
                symfony-require: [""]
                variant: [normal]
                include:
                    - php-version: '7.3'
                      dependencies: lowest
                      allowed-to-fail: false
                      variant: normal
                    - php-version: '8.0'
                      dependencies: highest
                      allowed-to-fail: true
                      variant: normal
                    - php-version: '7.4'
                      dependencies: highest
                      allowed-to-fail: false
                      variant: 'sonata-project/block-bundle:"4.*"'
                    - php-version: '7.4'
                      dependencies: highest
                      allowed-to-fail: false
                      symfony-require: 4.4.*
                      variant: 'symfony/symfony:"4.4.*"'

        steps:
            - name: Checkout
              uses: actions/checkout@v2

            - name: Install PHP with extensions
              uses: shivammathur/setup-php@v2
              with:
                  php-version: ${{ matrix.php-version }}
                  coverage: pcov
                  tools: composer:v1

            - name: Add PHPUnit matcher
              run: echo "::add-matcher::${{ runner.tool_cache }}/phpunit.json"

            - name: Configuration required for PHP 8.0
              if: matrix.php-version == '8.0'
              run: composer config platform.php 7.4.99

            - name: "Globally install symfony/flex"
              if: matrix.symfony-require != ''
              run: "composer global require --no-progress --no-scripts --no-plugins symfony/flex"

            - name: Install variant
              if: matrix.variant != 'normal' && !startsWith(matrix.variant, 'symfony/symfony')
              run: composer require ${{ matrix.variant }} --no-update

            - name: Allow unstable dependencies
              run: composer config minimum-stability dev

            - name: "Install Composer dependencies (${{ matrix.dependencies }})"
              uses: "ramsey/composer-install@v1"
              with:
                  dependency-versions: "${{ matrix.dependencies }}"
                  composer-options: "--prefer-dist --prefer-stable"

            - name: Run Tests with coverage
              run: make coverage

            - name: Send coverage to Codecov
              uses: codecov/codecov-action@v1
              with:
                  file: build/logs/clover.xml

OUTPUT;

        yield 'complete admin bundle' => [$result, $project];
    }
}
