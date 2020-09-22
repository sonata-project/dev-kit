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
        ];

        self::assertSame(
            $value,
            CombinedStatus::fromResponse($response)->toString()
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
