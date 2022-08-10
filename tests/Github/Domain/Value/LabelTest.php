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
use App\Github\Domain\Value\Label\Color;
use PHPUnit\Framework\TestCase;

final class LabelTest extends TestCase
{
    public function testThrowsExceptionIfNameIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label::fromValues(
            '',
            Color::fromString('ededed')
        );
    }

    public function testValid(): void
    {
        $label = Label::fromValues(
            'Test',
            Color::fromString('EDEDED')
        );

        static::assertSame('Test', $label->name());
        static::assertSame('ededed', $label->color()->toString());
        static::assertSame('#ededed', $label->color()->asHexCode());
        static::assertSame(
            [
                'color' => 'ededed',
                'name' => 'Test',
            ],
            $label->toGithubPayload()
        );
    }

    public function testRTM(): void
    {
        $label = Label::RTM();

        static::assertSame('RTM', $label->name());
        static::assertSame('ffffff', $label->color()->toString());
        static::assertSame('#ffffff', $label->color()->asHexCode());
    }

    public function testPendingAuthor(): void
    {
        $label = Label::PendingAuthor();

        static::assertSame('pending author', $label->name());
        static::assertSame('ededed', $label->color()->toString());
        static::assertSame('#ededed', $label->color()->asHexCode());
    }

    /**
     * @dataProvider equalsProvider
     */
    public function testEquals(bool $expected, Label $label, Label $other): void
    {
        static::assertSame(
            $expected,
            $label->equals($other)
        );
    }

    /**
     * @return iterable<array{bool, Label, Label}>
     */
    public function equalsProvider(): iterable
    {
        yield [
            true,
            Label::fromValues('RTM', Color::fromString('ffffff')),
            Label::RTM(),
        ];

        yield 'not equal, because color is taken into account' => [
            false,
            Label::fromResponse([
                'name' => 'foo',
                'color' => 'eeeeee',
            ]),
            Label::fromResponse([
                'name' => 'foo',
                'color' => 'ffffff',
            ]),
        ];

        yield 'equal, because of name and case insensitive' => [
            true,
            Label::fromResponse([
                'name' => 'Foo',
                'color' => 'ededed',
            ]),
            Label::fromResponse([
                'name' => 'foo',
                'color' => 'ededed',
            ]),
        ];

        yield 'equal, because of color and case insensitive' => [
            true,
            Label::fromResponse([
                'name' => 'foo',
                'color' => 'EDEDED',
            ]),
            Label::fromResponse([
                'name' => 'foo',
                'color' => 'ededed',
            ]),
        ];

        yield [
            false,
            Label::RTM(),
            Label::PendingAuthor(),
        ];
    }
}
