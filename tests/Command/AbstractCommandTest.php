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

namespace App\Tests\Command;

use App\Command\AbstractCommand;
use App\Domain\Value\Project;
use Github\Exception\RuntimeException;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class AbstractCommandTest extends TestCase
{
    private const array PROJECT_NAMES = ['admin-bundle', 'block-bundle', 'page-bundle'];

    public function testProcessProjectsSucceedsWhenNoProjectFails(): void
    {
        $processed = [];
        $tester = new CommandTester($this->createCommand(static function (Project $project) use (&$processed): void {
            $processed[] = $project->name();
        }));

        static::assertSame(Command::SUCCESS, $tester->execute([]));
        static::assertSame(self::PROJECT_NAMES, $processed);
        static::assertStringNotContainsString('Summary', $tester->getDisplay());
    }

    public function testProcessProjectsContinuesAfterAFailureAndFailsAtTheEnd(): void
    {
        $processed = [];
        $tester = new CommandTester($this->createCommand(static function (Project $project) use (&$processed): void {
            $processed[] = $project->name();

            if ('block-bundle' === $project->name()) {
                throw new RuntimeException('Bad credentials', 401);
            }
        }));

        static::assertSame(Command::FAILURE, $tester->execute([]));
        static::assertSame(self::PROJECT_NAMES, $processed);

        $display = $tester->getDisplay();

        static::assertStringContainsString('Failed with message: Bad credentials', $display);
        static::assertStringContainsString('Summary', $display);
        static::assertMatchesRegularExpression('/block-bundle\s+Bad credentials/', $display);
        static::assertStringContainsString('1 of 3 project(s) failed.', $display);
    }

    /**
     * @param \Closure(Project): void $callback
     */
    private function createCommand(\Closure $callback): Command
    {
        $projects = array_map(static function (string $name): Project {
            $package = new Package();
            $package->fromArray([
                'name' => \sprintf('sonata-project/%s', $name),
                'repository' => \sprintf('https://github.com/sonata-project/%s', $name),
            ]);

            return Project::fromValues($name, [
                'excluded_files' => [],
                'has_documentation' => false,
                'has_test_kernel' => false,
                'has_platform_tests' => false,
                'documentation_badge_slug' => null,
                'branches' => [],
            ], $package);
        }, self::PROJECT_NAMES);

        return new class($projects, $callback) extends AbstractCommand {
            /**
             * @param array<Project>          $projects
             * @param \Closure(Project): void $callback
             */
            public function __construct(
                private array $projects,
                private \Closure $callback,
            ) {
                parent::__construct('test');
            }

            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return $this->processProjects($this->projects, $this->callback);
            }
        };
    }
}
