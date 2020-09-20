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

namespace App\Tests\Github\Domain\Value;

use App\Github\Domain\Value\Label;
use PHPUnit\Framework\TestCase;

final class LabelTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label::fromString('');
    }

    /**
     * @test
     */
    public function fromString(): void
    {
        $value = 'foo';

        self::assertSame(
            $value,
            Label::fromString($value)->name()
        );
    }

    /**
     * @test
     */
    public function RTM(): void
    {
        $label = Label::RTM();

        self::assertSame(
            'RTM',
            $label->name()
        );
        self::assertNull($label->color());
    }

    /**
     * @test
     */
    public function PendingAuthor(): void
    {
        $label = Label::PendingAuthor();

        self::assertSame(
            'pending author',
            $label->name()
        );
        self::assertNull($label->color());
    }

    /**
     * @test
     *
     * @dataProvider equalsProvider
     */
    public function equals(bool $expected, Label $action, Label $other): void
    {
        self::assertSame(
            $expected,
            $action->equals($other)
        );
    }

    public function equalsProvider(): \Generator
    {
        yield [
            true,
            Label::fromString('RTM'),
            Label::RTM(),
        ];

        yield 'equals, because color is not taken into account' => [
            true,
            Label::fromResponse([
                'name' => 'foo',
                'color' => '1',
            ]),
            Label::fromResponse([
                'name' => 'foo',
                'color' => '2',
            ]),
        ];

        yield [
            false,
            Label::RTM(),
            Label::PendingAuthor(),
        ];
    }
}
