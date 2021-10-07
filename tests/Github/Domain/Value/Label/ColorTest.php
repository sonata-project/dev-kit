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

namespace App\Tests\Github\Domain\Value\Label;

use App\Github\Domain\Value\Label;
use PHPUnit\Framework\TestCase;

final class ColorTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label\Color::fromString('');
    }

    /**
     * @test
     */
    public function throwsExceptionIfColorStartsWithHash(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label\Color::fromString('#123454');
    }

    /**
     * @test
     */
    public function throwsExceptionIfColorLengthIsLessThan6(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label\Color::fromString('12345');
    }

    /**
     * @test
     */
    public function throwsExceptionIfColorLengthIsGreaterThan6(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label\Color::fromString('1234567');
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $color = Label\Color::fromString('EDEDED');

        static::assertSame('ededed', $color->toString());
        static::assertSame('#ededed', $color->asHexCode());
    }

    /**
     * @test
     *
     * @dataProvider equalsProvider
     */
    public function equals(bool $expected, Label\Color $color, Label\Color $other): void
    {
        static::assertSame(
            $expected,
            $color->equals($other)
        );
    }

    /**
     * @return iterable<array{bool, Label\Color, Label\Color}>
     */
    public function equalsProvider(): iterable
    {
        yield [
            true,
            Label\Color::fromString('ededed'),
            Label\Color::fromString('ededed'),
        ];

        yield 'equal, because of case insensitive' => [
            true,
            Label\Color::fromString('ededed'),
            Label\Color::fromString('EDEDED'),
        ];

        yield [
            false,
            Label\Color::fromString('111111'),
            Label\Color::fromString('222222'),
        ];
    }
}
