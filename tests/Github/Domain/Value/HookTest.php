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

use App\Github\Domain\Value\Hook;
use PHPUnit\Framework\TestCase;

final class HookTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Hook::fromResponse([]);
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $config = [
            'url' => $url = 'http://hook.de',
            'foo' => 'bar',
        ];

        $events = [
            'issue',
        ];

        $response = [
            'id' => $id = 123,
            'url' => $hookUrl = 'http://test.de',
            'active' => $active = true,
            'config' => $config,
            'events' => $events,
        ];

        $hook = Hook::fromResponse($response);

        static::assertSame($id, $hook->id());
        static::assertSame($hookUrl, $hook->url()->toString());
        static::assertSame($active, $hook->active());
        static::assertTrue($hook->config()->equals($config));
        static::assertSame($url, $hook->config()->url()->toString());
        static::assertTrue($hook->events()->equals($events));
    }
}
