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

namespace App\Tests\Util;

use App\Util\Util;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\TestCase;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class UtilTest extends TestCase
{
    /**
     * @dataProvider getRepositoryNameProvider
     */
    public function getRepositoryName(string $expected, string $repository): void
    {
        $package = new Package();
        $package->fromArray([
            'repostory' => $repository,
        ]);

        self::assertSame(
            $expected,
            Util::getRepositoryName($package)
        );
    }

    public function getRepositoryNameThrowsExceptionIfNameDoesNotContainSlash(string $expected, string $repository): void
    {
        $package = new Package();
        $package->fromArray([
            'repostory' => $repository = 'sonata-projectSonataAdminBundle',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Could not get repository name without vendor prefix for: %s',
            $repository
        ));

        Util::getRepositoryName($package);
    }

    public function getRepositoryNameThrowsExceptionIfNameEndsWithSlash(string $expected, string $repository): void
    {
        $package = new Package();
        $package->fromArray([
            'repostory' => $repository = 'sonata-projectSonataAdminBundle/',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'Could not get repository name without vendor prefix for: %s',
            $repository
        ));

        Util::getRepositoryName($package);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public function getRepositoryNameProvider(): iterable
    {
        yield ['SonataAdminBundle', 'sonata-project/SonataAdminBundle'];
        yield ['SonataAdminBundle', 'sonata-project/SonataAdminBundle.git'];
    }
}
