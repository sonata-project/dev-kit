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
    public function throwsExceptionIfNameIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label::fromValues(
            '',
            Label\Color::fromString('ededed')
        );
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $label = Label::fromValues(
            'Test',
            Label\Color::fromString('EDEDED')
        );

        self::assertSame('Test', $label->name());
        self::assertSame('ededed', $label->color()->toString());
        self::assertSame('#ededed', $label->color()->asHexCode());
        self::assertSame(
            [
                'color' => 'ededed',
                'name' => 'Test',
            ],
            $label->toGithubPayload()
        );
    }

    /**
     * @test
     */
    public function RTM(): void
    {
        $label = Label::RTM();

        self::assertSame('RTM', $label->name());
        self::assertSame('ffffff', $label->color()->toString());
        self::assertSame('#ffffff', $label->color()->asHexCode());
    }

    /**
     * @test
     */
    public function PendingAuthor(): void
    {
        $label = Label::PendingAuthor();

        self::assertSame('pending author', $label->name());
        self::assertSame('ededed', $label->color()->toString());
        self::assertSame('#ededed', $label->color()->asHexCode());
    }

    /**
     * @test
     *
     * @dataProvider equalsProvider
     */
    public function equals(bool $expected, Label $label, Label $other): void
    {
        self::assertSame(
            $expected,
            $label->equals($other)
        );
    }

    public function equalsProvider(): \Generator
    {
        yield [
            true,
            Label::fromValues('RTM', Label\Color::fromString('ffffff')),
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
