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
use PHPUnit\Framework\TestCase;

final class HeadTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfResponseArrayDoesNotContainKeyRef(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
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
    public function throwsExceptionIfResponseArrayContainKeyRefButEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
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
    public function throwsExceptionIfResponseArrayDoesNotContainKeySha(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
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
    public function throwsExceptionIfResponseArrayContainKeyShaButEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
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
    public function throwsExceptionIfResponseArrayDoesNotContainKeyRepo(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
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
    public function throwsExceptionIfResponseArrayContainKeyRepoButEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
            'ref' => 'ref',
            'sha' => 'sha',
            'repo' => [],
        ]);
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $response = [
            'ref' => $ref = 'foo',
            'sha' => $sha = 'sha',
            'repo' => [
                'owner' => [
                    'login' => $ownerLogin = 'foo-bar',
                ],
            ],
        ];

        $head = Head::fromResponse($response);

        self::assertSame($ref, $head->ref());
        self::assertSame($sha, $head->sha()->toString());
        self::assertSame($ownerLogin, $head->repo()->owner()->login());
    }
}
