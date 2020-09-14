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

namespace App\Tests\Github\Domain\Value\Webhook;

use App\Github\Domain\Value\Webhook\Action;
use App\Github\Domain\Value\Webhook\Event;
use PHPUnit\Framework\TestCase;

final class ActionTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Action::fromString('');
    }

    /**
     * @test
     */
    public function fromString(): void
    {
        $value = 'foo';

        self::assertSame(
            $value,
            Action::fromString($value)->toString()
        );
    }

    /**
     * @test
     */
    public function SYNCHRONIZE(): void
    {
        self::assertSame(
            'synchronize',
            Action::SYNCHRONIZE()->toString()
        );
    }

    /**
     * @test
     */
    public function CEATED(): void
    {
        self::assertSame(
            'created',
            Action::CREATED()->toString()
        );
    }

    /**
     * @test
     *
     * @dataProvider equalsProvider
     */
    public function equals(bool $expected, Action $action, Action $other): void
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
            Action::fromString('synchronize'),
            Action::SYNCHRONIZE()
        ];

        yield [
            false,
            Action::CREATED(),
            Action::SYNCHRONIZE()
        ];
    }

    /**
     * @test
     *
     * @dataProvider equalsProvider
     */
    public function equalsOneOf(bool $expected, Action $action, array $others): void
    {
        self::assertSame(
            $expected,
            $action->equalsOneOf($others)
        );
    }

    public function equalsOneOfProvider(): \Generator
    {
        yield [
            true,
            Action::fromString('synchronize'),
            [
                Action::SYNCHRONIZE(),
            ]
        ];

        yield [
            true,
            Action::fromString('synchronize'),
            [
                Action::SYNCHRONIZE(),
                Action::CREATED(),
            ]
        ];

        yield [
            false,
            Action::CREATED(),
            [
                Action::SYNCHRONIZE(),
            ]
        ];

        yield [
            false,
            Action::CREATED(),
            [
                Action::SYNCHRONIZE(),
                Action::fromString('synchronize'),
            ]
        ];
    }
}
