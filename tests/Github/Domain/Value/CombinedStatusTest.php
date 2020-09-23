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

use App\Github\Domain\Value\CombinedStatus;
use App\Github\Domain\Value\Status;
use App\Tests\Util\Factory\CombinedStatusResponseFactory;
use App\Tests\Util\Factory\StatusResponseFactory;
use PHPUnit\Framework\TestCase;

final class CombinedStatusTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse([]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfResponseArrayDoesNotContainKeyState(): void
    {
        $response = CombinedStatusResponseFactory::create();
        unset($response['state']);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStateIsEmptyString(): void
    {
        $response = CombinedStatusResponseFactory::create([
            'state' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStateIsUnknown(): void
    {
        $response = CombinedStatusResponseFactory::create([
            'state' => 'foo',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStatusesKeyDoesNotExist(): void
    {
        $response = CombinedStatusResponseFactory::create();
        unset($response['statuses']);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStatusesKeyIsEmptyArray(): void
    {
        $response = CombinedStatusResponseFactory::create([
            'statuses' => [],
        ]);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    /**
     * @test
     *
     * @dataProvider stateProvider
     */
    public function usesStateFromResponse(string $state): void
    {
        $response = CombinedStatusResponseFactory::create([
            'state' => $state,
        ]);

        self::assertSame(
            $state,
            CombinedStatus::fromResponse($response)->state()
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public function stateProvider(): \Generator
    {
        yield 'failure' => ['failure'];
        yield 'pending' => ['pending'];
        yield 'success' => ['success'];
    }

    /**
     * @test
     *
     * @dataProvider isSuccessfulProvider
     */
    public function isSuccessful(bool $expected, string $state): void
    {
        $response = CombinedStatusResponseFactory::create([
            'state' => $state,
        ]);

        self::assertSame(
            $expected,
            CombinedStatus::fromResponse($response)->isSuccessful()
        );
    }

    /**
     * @return \Generator<string, array{0: bool, 1: string}>
     */
    public function isSuccessfulProvider(): \Generator
    {
        yield 'failure' => [false, 'failure'];
        yield 'pending' => [false, 'pending'];
        yield 'success' => [true, 'success'];
    }

    /**
     * @test
     */
    public function usesStatusesFromResponse(): void
    {
        $response = CombinedStatusResponseFactory::create([
            'statuses' => $statuses = [
                Status::fromResponse(StatusResponseFactory::create()),
                Status::fromResponse(StatusResponseFactory::create()),
            ],
        ]);

        self::assertCount(2, $statuses);
        self::assertSame(
            $statuses,
            CombinedStatus::fromResponse($response)->statuses()
        );
    }
}
