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
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class StabilityTest extends TestCase
{
    use Helper;

    /**
     * @test
     *
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
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
        static::assertSame(
            'minor',
            Stability::fromString('MINOR')->toString()
        );
    }

    /**
     * @test
     */
    public function toStringReturnsLowercaseString(): void
    {
        static::assertSame(
            'minor',
            Stability::minor()->toString()
        );
    }

    /**
     * @test
     */
    public function toUppercaseStringReturnsUppercaseString(): void
    {
        static::assertSame(
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

        static::assertSame($value, $stability->toString());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function validProvider(): iterable
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
        static::assertSame(
            'patch',
            Stability::patch()->toString()
        );
    }

    /**
     * @test
     */
    public function minor()
    {
        static::assertSame(
            'minor',
            Stability::minor()->toString()
        );
    }

    /**
     * @test
     */
    public function pedantic()
    {
        static::assertSame(
            'pedantic',
            Stability::pedantic()->toString()
        );
    }

    /**
     * @test
     */
    public function unknown()
    {
        static::assertSame(
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
        static::assertSame(
            $expected,
            $stability->equals($other)
        );

        static::assertSame(
            !$expected,
            $stability->notEquals($other)
        );
    }

    /**
     * @return iterable<array{bool, Stability, Stability}>
     */
    public function equalsProvider(): iterable
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
