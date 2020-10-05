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
use App\Tests\Util\Factory\Github;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class CombinedStatusTest extends TestCase
{
    use Helper;

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
        $response = Github\Response\CombinedStatusFactory::create();
        unset($response['state']);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStateIsEmptyString(): void
    {
        $response = Github\Response\CombinedStatusFactory::create([
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
        $response = Github\Response\CombinedStatusFactory::create([
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
        $response = Github\Response\CombinedStatusFactory::create();
        unset($response['statuses']);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStatusesKeyIsEmptyArray(): void
    {
        $response = Github\Response\CombinedStatusFactory::create();
        $response['statuses'] = [];

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
        $response = Github\Response\CombinedStatusFactory::create([
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
        $response = Github\Response\CombinedStatusFactory::create([
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
        $response = Github\Response\CombinedStatusFactory::create([
            'statuses' => [
                $statusResponse1 = Github\Response\StatusFactory::create(),
                $statusResponse2 = Github\Response\StatusFactory::create(),
            ],
        ]);

        $combined = CombinedStatus::fromResponse($response);
        $statuses = $combined->statuses();

        self::assertCount(2, $statuses);
        self::assertStatusEqualsStatus(
            Status::fromResponse($statusResponse1),
            $statuses[0]
        );
        self::assertStatusEqualsStatus(
            Status::fromResponse($statusResponse2),
            $statuses[1]
        );
    }

    private static function assertStatusEqualsStatus(Status $expected, Status $other): void
    {
        self::assertSame($expected->state(), $other->state());
        self::assertSame($expected->description(), $other->description());
        self::assertSame($expected->targetUrl(), $other->targetUrl());
    }
}
