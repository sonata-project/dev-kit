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

namespace App\Tests\Config\Exception;

use App\Config\Exception\UnknownBranch;
use App\Domain\Value\Project;
use App\Tests\Domain\Value\ProjectTest;
use Ergebnis\Test\Util\Helper;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class UnknownBranchTest extends TestCase
{
    use Helper;

    public function testForName(): void
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

        $name = self::faker()->word();

        $unknownBranch = UnknownBranch::forName($project, $name);

        static::assertInstanceOf(
            \InvalidArgumentException::class,
            $unknownBranch
        );
        static::assertSame(
            sprintf(
                'Could not find branch with name "%s" for project "%s".',
                $name,
                $project->name()
            ),
            $unknownBranch->getMessage()
        );
    }
}
