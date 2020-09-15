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
    /**
     * @test
     */
    public function throwsExceptionIfPackageDoesNotContainSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must contain a "/"!');

        Variant::fromValues('sonata-projectdev-kit', '1.0');
    }

    /**
     * @test
     */
    public function throwsExceptionIfPackageEndsWithSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must not end with a "/"!');

        Variant::fromValues('sonata-project/dev-kit/', '1.0');
    }

    /**
     * @test
     */
    public function throwsExceptionIfPackageStartsWithSlash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must not start with a "/"!');

        Variant::fromValues('/sonata-project/dev-kit', '1.0');
    }

    /**
     * @test
     */
    public function throwsExceptionIfPackageIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Package must not be empty!');

        Variant::fromValues('', '1.0');
    }

    /**
     * @test
     */
    public function throwsExceptionIfVersionIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Version must not be empty!');

        Variant::fromValues('sonata-project/dev-kit', '');
    }

    /**
     * @test
     *
     * @dataProvider validProvider
     */
    public function valid(string $expected, string $package, string $version): void
    {
        $variant = Variant::fromValues($package, $version);

        self::assertSame($package, $variant->package());
        self::assertSame($version, $variant->version());
        self::assertSame($expected, $variant->toString());
    }

    /**
     * @return \Generator<array{0: string, 1: string, 2: string}>
     */
    public function validProvider(): \Generator
    {
        yield [
            'sonata-project/dev-kit:"1.*"',
            'sonata-project/dev-kit',
            '1',
        ];

        yield [
            'sonata-project/dev-kit:"1.1.*"',
            'sonata-project/dev-kit',
            '1.1',
        ];

        yield [
            'sonata-project/dev-kit:"dev-master"',
            'sonata-project/dev-kit',
            'dev-master',
        ];
    }
}
