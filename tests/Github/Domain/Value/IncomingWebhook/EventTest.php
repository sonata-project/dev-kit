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
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionFor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Event::fromString($value);
    }

    /**
     * @test
     *
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::untrimmed()
     */
    public function fromString(string $value): void
    {
        static::assertSame(
            trim($value),
            Event::fromString($value)->toString()
        );
    }

    /**
     * @test
     *
     * @dataProvider equalsProvider
     */
    public function equals(bool $expected, Event $event, Event $other): void
    {
        static::assertSame(
            $expected,
            $event->equals($other)
        );
    }

    public function equalsProvider(): \Generator
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
     * @test
     *
     * @dataProvider equalsOneOfProvider
     */
    public function equalsOneOf(bool $expected, Event $event, array $others): void
    {
        static::assertSame(
            $expected,
            $event->equalsOneOf($others)
        );
    }

    public function equalsOneOfProvider(): \Generator
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
