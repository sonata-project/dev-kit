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

use App\Github\Domain\Value\PullRequest;
use PHPUnit\Framework\TestCase;

final class PullRequestTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfRepsonseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PullRequest::fromResponse([]);
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $response = [
            'number' => $issue = 123,
            'title' => $title = 'Update dependecy',
            'updated_at' => $updatedAt = '2020-01-01 19:00:00',
            'base' => [
                'ref' => $baseRef = 'baseRef',
            ],
            'head' => [
                'ref' => $headRef = 'headRef',
                'sha' => $headSha = 'sha',
                'repo' => [
                    'owner' => [
                        'login' => $ownerLogin = 'ownerLogin',
                    ],
                ],
            ],
            'user' => [
                'login' => $userLogin = 'userLogin',
            ],
            'mergeable' => true,
        ];

        $pr = PullRequest::fromResponse($response);

        self::assertSame($issue, $pr->issue()->toInt());
        self::assertSame($title, $pr->title());
        self::assertSame($updatedAt, $pr->updatedAt()->format('Y-m-d H:i:s'));
        self::assertSame($baseRef, $pr->base()->ref());
        self::assertSame($headRef, $pr->head()->ref());
        self::assertSame($headSha, $pr->head()->sha()->toString());
        self::assertSame($ownerLogin, $pr->head()->repo()->owner()->login());
        self::assertSame($userLogin, $pr->user()->login());
        self::assertTrue($pr->isMergeable());
    }

    /**
     * @test
     */
    public function updatedWithinTheLast60SecondsReturnsTrue(): void
    {
        $now = new \DateTimeImmutable(
            'now',
            new \DateTimeZone('UTC')
        );

        $response = [
            'number' => $issue = 123,
            'title' => $title = 'Update dependecy',
            'updated_at' => $now->format('Y-m-d H:i:s'),
            'base' => [
                'ref' => $baseRef = 'baseRef',
            ],
            'head' => [
                'ref' => $headRef = 'headRef',
                'sha' => $headSha = 'sha',
                'repo' => [
                    'owner' => [
                        'login' => $ownerLogin = 'ownerLogin',
                    ],
                ],
            ],
            'user' => [
                'login' => $userLogin = 'userLogin',
            ],
            'mergeable' => true,
        ];

        $pr = PullRequest::fromResponse($response);

        self::assertTrue($pr->updatedWithinTheLast60Seconds());
    }

    /**
     * @test
     */
    public function updatedWithinTheLast60SecondsReturnsFalse(): void
    {
        $now = new \DateTimeImmutable(
            '2020-01-01 19:00:00',
            new \DateTimeZone('UTC')
        );

        $response = [
            'number' => $issue = 123,
            'title' => $title = 'Update dependecy',
            'updated_at' => $now->format('Y-m-d H:i:s'),
            'base' => [
                'ref' => $baseRef = 'baseRef',
            ],
            'head' => [
                'ref' => $headRef = 'headRef',
                'sha' => $headSha = 'sha',
                'repo' => [
                    'owner' => [
                        'login' => $ownerLogin = 'ownerLogin',
                    ],
                ],
            ],
            'user' => [
                'login' => $userLogin = 'userLogin',
            ],
            'mergeable' => true,
        ];

        $pr = PullRequest::fromResponse($response);

        self::assertFalse($pr->updatedWithinTheLast60Seconds());
    }
}
