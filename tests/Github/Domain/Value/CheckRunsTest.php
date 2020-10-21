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
use App\Tests\Util\Factory\Github;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class CheckRunsTest extends TestCase
{
    use Helper;

    /**
     * @test
     */
    public function throwsExceptionIfCheckRunsKeyDoesNotExist(): void
    {
        $response = Github\Response\CheckRunsFactory::create();
        unset($response['check_runs']);

        $this->expectException(\InvalidArgumentException::class);

        CheckRuns::fromResponse($response);
    }

    /**
     * @test
     */
    public function usesCheckRunsFromResponse(): void
    {
        $response = Github\Response\CheckRunsFactory::create([
            'check_runs' => [
                $response1 = Github\Response\CheckRunFactory::create(),
                $response2 = Github\Response\CheckRunFactory::create(),
            ],
        ]);

        $checkRuns = CheckRuns::fromResponse($response);
        $runs = $checkRuns->all();

        self::assertCount(2, $runs);
        self::assertCheckRunEqualsCheckRun(
            $checkRun1 = CheckRun::fromResponse($response1),
            $runs[$checkRun1->name()]
        );
        self::assertCheckRunEqualsCheckRun(
            $checkRun2 = CheckRun::fromResponse($response2),
            $runs[$checkRun2->name()]
        );
    }

    /**
     * @test
     */
    public function returnsSortedByName(): void
    {
        $response = Github\Response\CheckRunsFactory::create([
            'check_runs' => [
                Github\Response\CheckRunFactory::create([
                    'name' => 'foo',
                ]),
                Github\Response\CheckRunFactory::create([
                    'name' => 'Bar',
                ]),
                Github\Response\CheckRunFactory::create([
                    'name' => 'Zoo',
                ]),
            ],
        ]);

        $runs = CheckRuns::fromResponse($response)->all();

        self::assertSame(
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
        self::assertSame($expected->status(), $other->status());
        self::assertSame($expected->conclusion(), $other->conclusion());
        self::assertSame($expected->name(), $other->name());
        self::assertSame($expected->detailsUrl(), $other->detailsUrl());
        self::assertSame($expected->isSuccessful(), $other->isSuccessful());
    }
}
