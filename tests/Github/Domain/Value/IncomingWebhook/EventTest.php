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

use App\Github\Domain\Value\IncomingWebhook\Event;
use Ergebnis\Test\Util\DataProvider\StringProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    public function testThrowsExceptionFor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Event::fromString($value);
    }

    #[DataProviderExternal(StringProvider::class, 'untrimmed')]
    public function testFromString(string $value): void
    {
        static::assertSame(
            trim($value),
            Event::fromString($value)->toString()
        );
    }

    #[DataProvider('provideEqualsCases')]
    public function testEquals(bool $expected, Event $event, Event $other): void
    {
        static::assertSame(
            $expected,
            $event->equals($other)
        );
    }

    /**
     * @return iterable<array{bool, Event, Event}>
     */
    public static function provideEqualsCases(): iterable
    {
        yield [
            true,
            Event::fromString('issue'),
            Event::ISSUE(),
        ];

        yield [
            false,
            Event::fromString('issue'),
            Event::ISSUE_COMMENT(),
        ];
    }

    /**
     * @param array<Event> $others
     */
    #[DataProvider('provideEqualsOneOfCases')]
    public function testEqualsOneOf(bool $expected, Event $event, array $others): void
    {
        static::assertSame(
            $expected,
            $event->equalsOneOf($others)
        );
    }

    /**
     * @return iterable<array{bool, Event, array<Event>}>
     */
    public static function provideEqualsOneOfCases(): iterable
    {
        yield [
            true,
            Event::fromString('issue'),
            [
                Event::ISSUE(),
            ],
        ];

        yield [
            true,
            Event::fromString('issue'),
            [
                Event::ISSUE_COMMENT(),
                Event::ISSUE(),
            ],
        ];

        yield [
            false,
            Event::PULL_REQUEST(),
            [
                Event::ISSUE(),
            ],
        ];

        yield [
            false,
            Event::PULL_REQUEST(),
            [
                Event::ISSUE(),
                Event::fromString('issue_comment'),
            ],
        ];
    }
}
