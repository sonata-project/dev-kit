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
use App\Tests\Util\Factory\Github\Response\CombinedStatusFactory;
use App\Tests\Util\Factory\Github\Response\StatusFactory;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class CombinedStatusTest extends TestCase
{
    use Helper;

    public function testThrowsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse([]);
    }

    public function testThrowsExceptionIfResponseArrayDoesNotContainKeyState(): void
    {
        $response = CombinedStatusFactory::create();
        unset($response['state']);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    public function testThrowsExceptionIfStateIsEmptyString(): void
    {
        $response = CombinedStatusFactory::create([
            'state' => '',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    public function testThrowsExceptionIfStateIsUnknown(): void
    {
        $response = CombinedStatusFactory::create([
            'state' => 'foo',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    public function testThrowsExceptionIfStatusesKeyDoesNotExist(): void
    {
        $response = CombinedStatusFactory::create();
        unset($response['statuses']);

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    public function testThrowsExceptionIfStatusesKeyIsEmptyArrayAndStateIsSuccess(): void
    {
        $response = CombinedStatusFactory::create([
            'state' => 'success',
        ]);

        $response['statuses'] = [];

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    public function testThrowsExceptionIfStatusesKeyIsEmptyArrayAndStateIsFailure(): void
    {
        $response = CombinedStatusFactory::create([
            'state' => 'failure',
        ]);

        $response['statuses'] = [];

        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse($response);
    }

    public function testAllowStatusesKeyIsEmptyArrayAndStateIsPending(): void
    {
        $response = CombinedStatusFactory::create([
            'state' => 'pending',
        ]);

        $response['statuses'] = [];

        static::assertSame(
            [],
            CombinedStatus::fromResponse($response)->statuses()
        );
    }

    /**
     * @dataProvider stateProvider
     */
    public function testUsesStateFromResponse(string $state): void
    {
        $response = CombinedStatusFactory::create([
            'state' => $state,
        ]);

        static::assertSame(
            $state,
            CombinedStatus::fromResponse($response)->state()
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function stateProvider(): iterable
    {
        yield 'failure' => ['failure'];
        yield 'pending' => ['pending'];
        yield 'success' => ['success'];
    }

    /**
     * @dataProvider isSuccessfulProvider
     */
    public function testIsSuccessful(bool $expected, string $state): void
    {
        $response = CombinedStatusFactory::create([
            'state' => $state,
        ]);

        static::assertSame(
            $expected,
            CombinedStatus::fromResponse($response)->isSuccessful()
        );
    }

    /**
     * @return iterable<string, array{bool, string}>
     */
    public function isSuccessfulProvider(): iterable
    {
        yield 'failure' => [false, 'failure'];
        yield 'pending' => [false, 'pending'];
        yield 'success' => [true, 'success'];
    }

    public function testUsesStatusesFromResponse(): void
    {
        $response = CombinedStatusFactory::create([
            'statuses' => [
                $statusResponse1 = StatusFactory::create(),
                $statusResponse2 = StatusFactory::create(),
            ],
        ]);

        $combined = CombinedStatus::fromResponse($response);
        $statuses = $combined->statuses();

        static::assertCount(2, $statuses);
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
        static::assertSame($expected->state(), $other->state());
        static::assertSame($expected->description(), $other->description());
        static::assertSame($expected->targetUrl(), $other->targetUrl());
        static::assertSame($expected->isSuccessful(), $other->isSuccessful());
    }
}
