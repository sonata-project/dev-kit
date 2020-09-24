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

use App\Domain\Value\Stability;
use App\Tests\Util\Helper;
use PHPUnit\Framework\TestCase;

final class StabilityTest extends TestCase
{
    use Helper;

    /**
     * @test
     *
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::blank()
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionFor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Stability::fromString($value);
    }

    /**
     * @test
     */
    public function usesLowercaseForStability(): void
    {
        self::assertSame(
            'minor',
            Stability::fromString('MINOR')->toString()
        );
    }

    /**
     * @test
     */
    public function toStringReturnsLowercaseString(): void
    {
        self::assertSame(
            'minor',
            Stability::minor()->toString()
        );
    }

    /**
     * @test
     */
    public function toUppercaseStringReturnsUppercaseString(): void
    {
        self::assertSame(
            'MINOR',
            Stability::minor()->toUppercaseString()
        );
    }

    /**
     * @test
     *
     * @dataProvider validProvider
     */
    public function valid(string $value): void
    {
        $stability = Stability::fromString($value);

        self::assertSame($value, $stability->toString());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public function validProvider(): \Generator
    {
        yield 'unknown' => ['unknown'];
        yield 'minor' => ['minor'];
        yield 'patch' => ['patch'];
    }

    /**
     * @test
     */
    public function patch()
    {
        self::assertSame(
            'patch',
            Stability::patch()->toString()
        );
    }

    /**
     * @test
     */
    public function minor()
    {
        self::assertSame(
            'minor',
            Stability::minor()->toString()
        );
    }

    /**
     * @test
     */
    public function pedantic()
    {
        self::assertSame(
            'pedantic',
            Stability::pedantic()->toString()
        );
    }

    /**
     * @test
     */
    public function unknown()
    {
        self::assertSame(
            'unknown',
            Stability::unknown()->toString()
        );
    }

    /**
     * @test
     *
     * @dataProvider equalsProvider
     */
    public function equals(bool $expected, Stability $stability, Stability $other): void
    {
        self::assertSame(
            $expected,
            $stability->equals($other)
        );

        self::assertSame(
            !$expected,
            $stability->notEquals($other)
        );
    }

    public function equalsProvider(): \Generator
    {
        yield [
            true,
            Stability::unknown(),
            Stability::fromString('unknown'),
        ];

        yield 'equal, because of case insensitive' => [
            true,
            Stability::fromString('minor'),
            Stability::fromString('MINOR'),
        ];

        yield [
            false,
            Stability::minor(),
            Stability::patch(),
        ];
    }
}
