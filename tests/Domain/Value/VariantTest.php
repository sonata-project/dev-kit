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

use App\Domain\Value\Variant;
use PHPUnit\Framework\TestCase;

final class VariantTest extends TestCase
{
    public function testThrowsExceptionIfPackageDoesNotContainSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must contain a "/"!');

        Variant::fromValues('sonata-projectdev-kit', '1.0.*');
    }

    public function testThrowsExceptionIfPackageEndsWithSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must not end with a "/"!');

        Variant::fromValues('sonata-project/dev-kit/', '1.0.*');
    }

    public function testThrowsExceptionIfPackageStartsWithSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must not start with a "/"!');

        Variant::fromValues('/sonata-project/dev-kit', '1.0.*');
    }

    public function testThrowsExceptionIfPackageIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must not be empty!');

        Variant::fromValues('', '1.0.*');
    }

    public function testThrowsExceptionIfVersionIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Version must not be empty!');

        Variant::fromValues('sonata-project/dev-kit', '');
    }

    /**
     * @dataProvider validProvider
     */
    public function testValid(string $expected, string $package, string $version): void
    {
        $variant = Variant::fromValues($package, $version);

        static::assertSame($package, $variant->package());
        static::assertSame($version, $variant->version());
        static::assertSame($expected, $variant->toString());
    }

    /**
     * @return iterable<array{string, string, string}>
     */
    public function validProvider(): iterable
    {
        yield [
            'sonata-project/dev-kit:"1.*"',
            'sonata-project/dev-kit',
            '1.*',
        ];

        yield [
            'sonata-project/dev-kit:"1.1.*"',
            'sonata-project/dev-kit',
            '1.1.*',
        ];

        yield [
            'sonata-project/dev-kit:"dev-master"',
            'sonata-project/dev-kit',
            'dev-master',
        ];
    }
}
