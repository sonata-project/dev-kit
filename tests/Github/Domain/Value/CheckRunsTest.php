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

use App\Github\Domain\Value\CheckRun;
use App\Github\Domain\Value\CheckRuns;
use App\Tests\Util\Factory\Github\Response\CheckRunFactory;
use App\Tests\Util\Factory\Github\Response\CheckRunsFactory;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class CheckRunsTest extends TestCase
{
    use Helper;

    public function testThrowsExceptionIfCheckRunsKeyDoesNotExist(): void
    {
        $response = CheckRunsFactory::create();
        unset($response['check_runs']);

        $this->expectException(\InvalidArgumentException::class);

        CheckRuns::fromResponse($response);
    }

    /**
     * @dataProvider isSuccessfulProvider
     */
    public function testIsSuccessful(bool $expected, string $conclusion): void
    {
        $response = CheckRunsFactory::create([
            'check_runs' => [
                CheckRunFactory::create([
                    'conclusion' => $conclusion,
                ]),
            ],
        ]);

        static::assertSame(
            $expected,
            CheckRuns::fromResponse($response)->isSuccessful()
        );
    }

    /**
     * @return iterable<string, array{bool, string}>
     */
    public function isSuccessfulProvider(): iterable
    {
        yield 'action_required' => [false, 'action_required'];
        yield 'cancelled' => [false, 'cancelled'];
        yield 'failure' => [false, 'failure'];
        yield 'neutral' => [false, 'neutral'];
        yield 'skipped' => [false, 'skipped'];
        yield 'stale' => [false, 'stale'];
        yield 'success' => [true, 'success'];
        yield 'timed_out' => [false, 'timed_out'];
    }

    public function testUsesCheckRunsFromResponse(): void
    {
        $response = CheckRunsFactory::create([
            'check_runs' => [
                $response1 = CheckRunFactory::create(),
                $response2 = CheckRunFactory::create(),
            ],
        ]);

        $checkRuns = CheckRuns::fromResponse($response);
        $runs = $checkRuns->all();

        static::assertCount(2, $runs);
        self::assertCheckRunEqualsCheckRun(
            $checkRun1 = CheckRun::fromResponse($response1),
            $runs[$checkRun1->name()]
        );
        self::assertCheckRunEqualsCheckRun(
            $checkRun2 = CheckRun::fromResponse($response2),
            $runs[$checkRun2->name()]
        );
    }

    public function testReturnsSortedByName(): void
    {
        $response = CheckRunsFactory::create([
            'check_runs' => [
                CheckRunFactory::create([
                    'name' => 'foo',
                ]),
                CheckRunFactory::create([
                    'name' => 'Bar',
                ]),
                CheckRunFactory::create([
                    'name' => 'Zoo',
                ]),
            ],
        ]);

        $runs = CheckRuns::fromResponse($response)->all();

        static::assertSame(
            [
                'Bar',
                'foo',
                'Zoo',
            ],
            array_keys($runs)
        );
    }

    private static function assertCheckRunEqualsCheckRun(CheckRun $expected, CheckRun $other): void
    {
        static::assertSame($expected->status(), $other->status());
        static::assertSame($expected->conclusion(), $other->conclusion());
        static::assertSame($expected->name(), $other->name());
        static::assertSame($expected->detailsUrl(), $other->detailsUrl());
        static::assertSame($expected->isSuccessful(), $other->isSuccessful());
    }
}
