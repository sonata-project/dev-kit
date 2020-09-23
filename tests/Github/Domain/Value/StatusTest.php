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
use App\Tests\Util\Factory\StatusResponseFactory;
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
        $response = StatusResponseFactory::create();
        unset($response['state']);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStateIsEmptyString(): void
    {
        $response = StatusResponseFactory::create([
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
        $response = StatusResponseFactory::create([
            'state' => 'foo',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfDescriptionKeyDoesNotExist(): void
    {
        $response = StatusResponseFactory::create();
        unset($response['description']);

        $this->expectException(\InvalidArgumentException::class);

        Status::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfDescriptionKeyIsEmptyString(): void
    {
        $response = StatusResponseFactory::create([
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
        $response = StatusResponseFactory::create([
            'state' => $state,
        ]);

        self::assertSame(
            $state,
            Status::fromResponse($response)->state()
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public function stateProvider(): \Generator
    {
        yield 'error' => ['error'];
        yield 'pending' => ['pending'];
        yield 'success' => ['success'];
    }

    /**
     * @test
     */
    public function usesDescriptionFromResponse(): void
    {
        $response = StatusResponseFactory::create();

        self::assertSame(
            $response['description'],
            Status::fromResponse($response)->description()
        );
    }

    /**
     * @test
     */
    public function usesTargetUrlFromResponse(): void
    {
        $response = StatusResponseFactory::create();

        self::assertSame(
            $response['target_url'],
            Status::fromResponse($response)->targetUrl()
        );
    }
}
