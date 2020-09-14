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
            Label::fromString($value)->toString()
        );
    }

    /**
     * @test
     */
    public function RTM(): void
    {
        self::assertSame(
            'RTM',
            Label::RTM()->toString()
        );
    }

    /**
     * @test
     */
    public function PendingAuthor(): void
    {
        self::assertSame(
            'pending author',
            Label::PendingAuthor()->toString()
        );
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

        yield [
            false,
            Label::RTM(),
            Label::PendingAuthor(),
        ];
    }
}
