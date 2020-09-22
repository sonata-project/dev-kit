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
use PHPUnit\Framework\TestCase;

final class CombinedStatusTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfRepsonseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse([]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayDoesNotContainKeyState(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse([
            'foo' => 'bar',
            'statuses' => [
                [
                    'state' => 'success',
                    'description' => 'foo',
                    'target_url' => 'https://test.de',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse([
            'state' => '',
            'statuses' => [
                [
                    'state' => 'success',
                    'description' => 'foo',
                    'target_url' => 'https://test.de',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfValueIsUnknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse([
            'state' => 'foo',
            'statuses' => [
                [
                    'state' => 'success',
                    'description' => 'foo',
                    'target_url' => 'https://test.de',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStatusesKeyDoesNotExist(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse([
            'state' => 'success',
            'foo' => [],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfStatusesKeyIsEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CombinedStatus::fromResponse([
            'state' => 'success',
            'statuses' => [],
        ]);
    }

    /**
     * @test
     *
     * @dataProvider validProvider
     */
    public function valid(string $value): void
    {
        $response = [
            'state' => $value,
            'statuses' => [
                [
                    'state' => 'success',
                    'description' => 'foo',
                    'target_url' => 'https://test.de',
                ],
            ],
        ];

        $combined = CombinedStatus::fromResponse($response);

        self::assertSame(
            $value,
            $combined->state()
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public function validProvider(): \Generator
    {
        yield 'success' => ['success'];
        yield 'pending' => ['pending'];
        yield 'failure' => ['failure'];
    }

    /**
     * @test
     *
     * @dataProvider isSuccessfulProvider
     */
    public function isSuccessful(bool $expected, string $value): void
    {
        $response = [
            'state' => $value,
            'statuses' => [
                [
                    'state' => 'success',
                    'description' => 'foo',
                    'target_url' => 'https://test.de',
                ],
            ],
        ];

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
        yield 'success' => [true, 'success'];
        yield 'pending' => [false, 'pending'];
        yield 'failure' => [false, 'failure'];
    }
}
