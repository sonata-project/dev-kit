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

namespace App\Tests\Github\Exception;

use App\Domain\Value\Repository;
use App\Github\Exception\LatestReleaseNotFound;
use Ergebnis\Test\Util\Helper;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;

final class LatestReleaseNotFoundTest extends TestCase
{
    use Helper;

    /**
     * @test
     */
    public function forRepository(): void
    {
        $package = new Package();
        $package->fromArray([
            'name' => $packageName = 'sonata-project/admin-bundle',
            'repository' => 'https://github.com/sonata-project/SonataAdminBundle',
        ]);

        $repository = Repository::fromPackage($package);

        $latestReleaseNotFound = LatestReleaseNotFound::forRepository($repository);

        static::assertInstanceOf(
            \RuntimeException::class,
            $latestReleaseNotFound
        );
        static::assertSame(
            sprintf(
                'Could not find latest Release for "%s".',
                $repository->toString()
            ),
            $latestReleaseNotFound->getMessage()
        );
    }
}
