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

namespace App\Tests\Action\Exception;

use App\Action\Exception\NoPullRequestsMergedSinceLastRelease;
use App\Domain\Value\Project;
use App\Tests\Domain\Value\ProjectTest;
use Ergebnis\Test\Util\Helper;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class NoPullRequestMergedSinceLastReleaseTest extends TestCase
{
    use Helper;

    public function testForProject(): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $config = Yaml::parse(ProjectTest::DEFAULT_CONFIG);

        static::assertIsArray($config);
        static::assertArrayHasKey(ProjectTest::DEFAULT_CONFIG_NAME, $config);

        $project = Project::fromValues(
            ProjectTest::DEFAULT_CONFIG_NAME,
            $config[ProjectTest::DEFAULT_CONFIG_NAME],
            $package
        );
        $branch = $project->unstableBranch();

        $lastRelease = new \DateTimeImmutable($datetime = '2020-01-01 10:00:01');

        $cannotDetermineNextRelease = NoPullRequestsMergedSinceLastRelease::forBranch(
            $project,
            $branch,
            $lastRelease
        );

        static::assertInstanceOf(
            \RuntimeException::class,
            $cannotDetermineNextRelease
        );
        static::assertSame(
            \sprintf(
                'No pull requests merged since last release "%s" for branch "%s" of project "%s".',
                $datetime,
                $branch->name(),
                $project->name()
            ),
            $cannotDetermineNextRelease->getMessage()
        );
    }
}
