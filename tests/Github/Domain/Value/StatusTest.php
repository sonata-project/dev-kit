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
    /**
     * @test
     */
    public function throwsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse([]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfResponseArrayDoesNotContainKeyState(): void
    {
        $response = StatusFactory::create();
        unset($response['state']);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStateIsEmptyString(): void
    {
        $response = StatusFactory::create([
            'state' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStateIsUnknown(): void
    {
        $response = StatusFactory::create([
            'state' => 'foo',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfContextKeyDoesNotExist(): void
    {
        $response = StatusFactory::create();
        unset($response['context']);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfContextKeyIsEmptyString(): void
    {
        $response = StatusFactory::create([
            'context' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfDescriptionKeyDoesNotExist(): void
    {
        $response = StatusFactory::create();
        unset($response['description']);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfDescriptionKeyIsEmptyString(): void
    {
        $response = StatusFactory::create([
            'description' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     *
     * @dataProvider stateProvider
     */
    public function usesStateFromResponse(string $state): void
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

    /**
     * @test
     */
    public function usesDescriptionFromResponse(): void
    {
        $response = StatusFactory::create();

        static::assertSame(
            $response['description'],
            Status::fromResponse($response)->description()
        );
    }

    /**
     * @test
     */
    public function usesTargetUrlFromResponse(): void
    {
        $response = StatusFactory::create();

        static::assertSame(
            $response['target_url'],
            Status::fromResponse($response)->targetUrl()
        );
    }
}
