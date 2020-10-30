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
        self::assertSame(
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
        self::assertSame(
            $expected,
            $event->equals($other)
        );
    }

    public function equalsProvider(): \Generator
    {
        yield [
            true,
            Event::fromString('issue'),
            Event::fromString('issue'),
        ];

        yield [
            false,
            Event::fromString('issue'),
            Event::fromString('issue_comment'),
        ];
    }

    /**
     * @test
     *
     * @dataProvider equalsOneOfProvider
     */
    public function equalsOneOf(bool $expected, Event $event, array $others): void
    {
        self::assertSame(
            $expected,
            $event->equalsOneOf($others)
        );
    }

    public function equalsOneOfProvider(): \Generator
    {
        yield [
            true,
            Event::fromString('synchronize'),
            [
                Event::SYNCHRONIZE(),
            ],
        ];

        yield [
            true,
            Event::fromString('synchronize'),
            [
                Event::SYNCHRONIZE(),
                Event::CREATED(),
            ],
        ];

        yield [
            false,
            Event::CREATED(),
            [
                Event::SYNCHRONIZE(),
            ],
        ];

        yield [
            false,
            Event::CREATED(),
            [
                Event::SYNCHRONIZE(),
                Event::fromString('synchronize'),
            ],
        ];
    }
}
