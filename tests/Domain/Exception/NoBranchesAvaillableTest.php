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

namespace App\Tests\Domain\Exception;

use App\Domain\Exception\NoBranchesAvailable;
use App\Domain\Value\Project;
use App\Tests\Domain\Value\ProjectTest;
use App\Tests\Util\Helper;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class NoBranchesAvaillableTest extends TestCase
{
    use Helper;

    /**
     * @test
     */
    public function forProject()
    {
        $package = new Package();
        $package->fromArray([
            'name' => $packageName = 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $config = Yaml::parse(ProjectTest::DEFAULT_CONFIG);

        $project = Project::fromValues(
            ProjectTest::DEFAULT_CONFIG_NAME,
            $config[ProjectTest::DEFAULT_CONFIG_NAME],
            $package
        );

        $noBranchesAvailable = NoBranchesAvailable::forProject($project);

        self::assertInstanceOf(
            \InvalidArgumentException::class,
            $noBranchesAvailable
        );
        self::assertSame(
            sprintf(
                'Project "%s" has no branches configured.',
                $project->name()
            ),
            $noBranchesAvailable->getMessage()
        );
    }
}
