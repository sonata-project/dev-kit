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

        Label::fromValues('', 'ededed');
    }

    /**
     * @test
     */
    public function throwsExceptionIfColorIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label::fromValues('foo', '');
    }

    /**
     * @test
     */
    public function throwsExceptionIfColorStartsWithHash(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label::fromValues('foo', '#123454');
    }

    /**
     * @test
     */
    public function throwsExceptionIfColorLengthIsLessThan6(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label::fromValues('foo', '12345');
    }

    /**
     * @test
     */
    public function throwsExceptionIfColorLengthIsGreaterThan6(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Label::fromValues('foo', '1234567');
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $label = Label::fromValues(
            'Test',
            'EDEDED'
        );

        self::assertSame('Test', $label->name());
        self::assertSame('ededed', $label->color());
        self::assertSame('#ededed', $label->colorWithLeadingHash());
    }

    /**
     * @test
     */
    public function RTM(): void
    {
        $label = Label::RTM();

        self::assertSame('RTM', $label->name());
        self::assertSame('ffffff', $label->color());
        self::assertSame('#ffffff', $label->colorWithLeadingHash());
    }

    /**
     * @test
     */
    public function PendingAuthor(): void
    {
        $label = Label::PendingAuthor();

        self::assertSame('pending author', $label->name());
        self::assertSame('ededed', $label->color());
        self::assertSame('#ededed', $label->colorWithLeadingHash());
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
            Label::fromValues('RTM', 'ffffff'),
            Label::RTM(),
        ];

        yield 'not equal, because color is taken into account' => [
            false,
            Label::fromResponse([
                'name' => 'foo',
                'color' => '1',
            ]),
            Label::fromResponse([
                'name' => 'foo',
                'color' => '2',
            ]),
        ];

        yield 'not equal, because of case sensitive' => [
            false,
            Label::fromResponse([
                'name' => 'Foo',
                'color' => '1',
            ]),
            Label::fromResponse([
                'name' => 'foo',
                'color' => '1',
            ]),
        ];

        yield [
            false,
            Label::RTM(),
            Label::PendingAuthor(),
        ];
    }
}
