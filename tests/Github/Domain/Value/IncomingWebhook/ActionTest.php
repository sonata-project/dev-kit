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

namespace App\Tests\Github\Domain\Value\IncomingWebhook;

use App\Github\Domain\Value\IncomingWebhook\Action;
use Ergebnis\Test\Util\DataProvider\StringProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class ActionTest extends TestCase
{
    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    public function testThrowsExceptionFor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Action::fromString($value);
    }

    #[DataProviderExternal(StringProvider::class, 'untrimmed')]
    public function testFromString(string $value): void
    {
        static::assertSame(
            trim($value),
            Action::fromString($value)->toString()
        );
    }

    public function testSYNCHRONIZE(): void
    {
        static::assertSame(
            'synchronize',
            Action::SYNCHRONIZE()->toString()
        );
    }

    public function testCREATED(): void
    {
        static::assertSame(
            'created',
            Action::CREATED()->toString()
        );
    }

    #[DataProvider('provideEqualsCases')]
    public function testEquals(bool $expected, Action $action, Action $other): void
    {
        static::assertSame(
            $expected,
            $action->equals($other)
        );
    }

    /**
     * @return iterable<array{bool, Action, Action}>
     */
    public static function provideEqualsCases(): iterable
    {
        yield [
            true,
            Action::fromString('synchronize'),
            Action::SYNCHRONIZE(),
        ];

        yield [
            false,
            Action::CREATED(),
            Action::SYNCHRONIZE(),
        ];
    }

    /**
     * @param array<Action> $others
     */
    #[DataProvider('provideEqualsOneOfCases')]
    public function testEqualsOneOf(bool $expected, Action $action, array $others): void
    {
        static::assertSame(
            $expected,
            $action->equalsOneOf($others)
        );
    }

    /**
     * @return iterable<array{bool, Action, array<Action>}>
     */
    public static function provideEqualsOneOfCases(): iterable
    {
        yield [
            true,
            Action::fromString('synchronize'),
            [
                Action::SYNCHRONIZE(),
            ],
        ];

        yield [
            true,
            Action::fromString('synchronize'),
            [
                Action::SYNCHRONIZE(),
                Action::CREATED(),
            ],
        ];

        yield [
            false,
            Action::CREATED(),
            [
                Action::SYNCHRONIZE(),
            ],
        ];

        yield [
            false,
            Action::CREATED(),
            [
                Action::SYNCHRONIZE(),
                Action::fromString('synchronize'),
            ],
        ];
    }
}
