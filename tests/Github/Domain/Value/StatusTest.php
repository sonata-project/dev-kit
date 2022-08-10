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

use App\Github\Domain\Value\Status;
use App\Tests\Util\Factory\Github\Response\StatusFactory;
use PHPUnit\Framework\TestCase;

final class StatusTest extends TestCase
{
    public function testThrowsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse([]);
    }

    public function testThrowsExceptionIfResponseArrayDoesNotContainKeyState(): void
    {
        $response = StatusFactory::create();
        unset($response['state']);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    public function testThrowsExceptionIfStateIsEmptyString(): void
    {
        $response = StatusFactory::create([
            'state' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    public function testThrowsExceptionIfStateIsUnknown(): void
    {
        $response = StatusFactory::create([
            'state' => 'foo',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    public function testThrowsExceptionIfContextKeyDoesNotExist(): void
    {
        $response = StatusFactory::create();
        unset($response['context']);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    public function testThrowsExceptionIfContextKeyIsEmptyString(): void
    {
        $response = StatusFactory::create([
            'context' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    public function testThrowsExceptionIfDescriptionKeyDoesNotExist(): void
    {
        $response = StatusFactory::create();
        unset($response['description']);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    public function testThrowsExceptionIfDescriptionKeyIsEmptyString(): void
    {
        $response = StatusFactory::create([
            'description' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @dataProvider stateProvider
     */
    public function testUsesStateFromResponse(string $state): void
    {
        $response = StatusFactory::create([
            'state' => $state,
        ]);

        static::assertSame(
            $state,
            Status::fromResponse($response)->state()
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function stateProvider(): iterable
    {
        yield 'error' => ['error'];
        yield 'failure' => ['failure'];
        yield 'pending' => ['pending'];
        yield 'success' => ['success'];
    }

    public function testUsesDescriptionFromResponse(): void
    {
        $response = StatusFactory::create();

        static::assertSame(
            $response['description'],
            Status::fromResponse($response)->description()
        );
    }

    public function testUsesTargetUrlFromResponse(): void
    {
        $response = StatusFactory::create();

        static::assertSame(
            $response['target_url'],
            Status::fromResponse($response)->targetUrl()
        );
    }
}
