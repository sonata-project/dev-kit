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

use App\Domain\Value\Repository;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    /**
     * @test
     */
    public function valid(): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => 'sonata-project/admin-bundle',
            'repository' => $repositoryName = 'sonata-project/admin-bundle.git',
        ]);

        $repository = Repository::fromPackage($package);

        self::assertSame('sonata-project/admin-bundle', $repository->name());
        self::assertSame('sonata-project', $repository->vendor());
        self::assertSame('admin-bundle', $repository->packageName());
    }
}
