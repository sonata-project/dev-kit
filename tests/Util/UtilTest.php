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
     * @test
     *
     * @dataProvider getRepositoryNameWithoutVendorPrefixProvider
     */
    public function getRepositoryNameWithoutVendorPrefix(string $expected, string $repository): void
    {
        $package = new Package();
        $package->fromArray([
            'repository' => $repository,
        ]);

        self::assertSame(
            $expected,
            Util::getRepositoryNameWithoutVendorPrefix($package)
        );
    }

    /**
     * @test
     */
    public function getRepositoryNameWithoutVendorPrefixThrowsExceptionIfNameDoesNotContainSlash(): void
    {
        $package = new Package();
        $package->fromArray([
            'repository' => $repository = 'sonata-projectSonataAdminBundle',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Repository name must contain a slash: %s',
            $repository
        ));

        Util::getRepositoryNameWithoutVendorPrefix($package);
    }

    /**
     * @test
     */
    public function getRepositoryNameWithoutVendorPrefixThrowsExceptionIfNameEndsWithSlash(): void
    {
        $package = new Package();
        $package->fromArray([
            'repository' => $repository = 'https://github.com/sonata-project/SonataAdminBundle/',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Repository name must not end with a slash: %s',
            $repository
        ));

        Util::getRepositoryNameWithoutVendorPrefix($package);
    }

    /**
     * @return iterable<array{0: string, 1: string}>
     */
    public function getRepositoryNameWithoutVendorPrefixProvider(): iterable
    {
        yield [
            'SonataAdminBundle',
            'https://github.com/sonata-project/SonataAdminBundle',
        ];

        yield [
            'SonataAdminBundle',
            'https://github.com/sonata-project/SonataAdminBundle.git',
        ];
    }
}
