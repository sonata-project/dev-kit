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

namespace App\Tests\Github\Domain\Value\PullRequest;

use App\Github\Domain\Value\PullRequest\Head;
use App\Github\Domain\Value\PullRequest\Head\Repo;
use PHPUnit\Framework\TestCase;

final class HeadTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfRepsonseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayDoesNotContainKeyRef(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([
            'foo' => 'bar',
            'sha' => 'sha',
            'repo' => [
                'owner' => [
                    'login' => 'repo',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayContainKeyRefButEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([
            'ref' => '',
            'sha' => 'sha',
            'repo' => [
                'owner' => [
                    'login' => 'repo',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayDoesNotContainKeySha(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([
            'ref' => 'ref',
            'foo' => 'bar',
            'repo' => [
                'owner' => [
                    'login' => 'repo',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayContainKeyShaButEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([
            'ref' => 'ref',
            'sha' => '',
            'repo' => [
                'owner' => [
                    'login' => 'repo',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayDoesNotContainKeyRepo(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([
            'ref' => 'ref',
            'sha' => 'sha',
            'foo' => [
                'owner' => [
                    'login' => 'repo',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayContainKeyShaButEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([
            'ref' => 'ref',
            'sha' => 'sha',
            'repo' => [],
        ]);
    }

    /**
     * @test
     */
    public function owner(): void
    {
        $response = [
            'ref' => $ref = 'foo',
            'owner' => [
                'login' => $value = 'foo-bar',
            ],
        ];

        self::assertSame(
            $value,
            Repo::fromResponse($response)->owner()->login()
        );
    }
}
