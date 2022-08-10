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
use App\Github\Domain\Value\Label\Color;
use PHPUnit\Framework\TestCase;

final class ColorTest extends TestCase
{
    public function testThrowsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Color::fromString('');
    }

    public function testThrowsExceptionIfColorStartsWithHash(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Color::fromString('#123454');
    }

    public function testThrowsExceptionIfColorLengthIsLessThan6(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Color::fromString('12345');
    }

    public function testThrowsExceptionIfColorLengthIsGreaterThan6(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Color::fromString('1234567');
    }

    public function testValid(): void
    {
        $color = Color::fromString('EDEDED');

        static::assertSame('ededed', $color->toString());
        static::assertSame('#ededed', $color->asHexCode());
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(bool $expected, Color $color, Color $other): void
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
            Color::fromString('ededed'),
            Color::fromString('ededed'),
        ];

        yield 'equal, because of case insensitive' => [
            true,
            Color::fromString('ededed'),
            Color::fromString('EDEDED'),
        ];

        yield [
            false,
            Color::fromString('111111'),
            Color::fromString('222222'),
        ];
    }
}
