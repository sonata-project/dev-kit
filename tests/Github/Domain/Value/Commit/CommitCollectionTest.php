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

namespace App\Tests\Github\Domain\Value\Commit;

use App\Github\Domain\Value\Commit;
use App\Github\Domain\Value\Commit\CommitCollection;
use PHPUnit\Framework\TestCase;

final class CommitCollectionTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CommitCollection::from([]);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function throwsExceptionIfValueIsNotEmptyButNotAllInstanceOfCommitClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore-next-line
        CommitCollection::from(['foo']);
    }

    /**
     * @test
     */
    public function implementsCountable(): void
    {
        $commits = [
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => 'foo bar baz',
                    'committer' => [
                        'date' => '2020-01-01 19:00:00',
                    ],
                ],
            ]),
        ];

        static::assertInstanceOf(
            \Countable::class,
            CommitCollection::from($commits)
        );
    }

    /**
     * @test
     */
    public function countByMethodAndInterface(): void
    {
        $commits = [
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => 'foo bar baz',
                    'committer' => [
                        'date' => '2020-01-01 19:00:00',
                    ],
                ],
            ]),
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => 'foo bar',
                    'committer' => [
                        'date' => '2020-01-01 18:00:00',
                    ],
                ],
            ]),
        ];

        $collection = CommitCollection::from($commits);

        static::assertCount(2, $collection);
    }

    /**
     * @test
     */
    public function uniqueCount(): void
    {
        $commits = [
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => 'foo bar baz',
                    'committer' => [
                        'date' => '2020-01-01 19:00:00',
                    ],
                ],
            ]),
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => 'foo bar baz',
                    'committer' => [
                        'date' => '2020-01-01 18:00:00',
                    ],
                ],
            ]),
        ];

        static::assertSame(
            1,
            CommitCollection::from($commits)->uniqueCount()
        );
    }

    /**
     * @test
     */
    public function firstMessage(): void
    {
        $commits = [
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => $firstMessage = 'foo bar baz',
                    'committer' => [
                        'date' => '2020-01-01 19:00:00',
                    ],
                ],
            ]),
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => 'foo bar',
                    'committer' => [
                        'date' => '2020-01-01 18:00:00',
                    ],
                ],
            ]),
        ];

        static::assertSame(
            $firstMessage,
            CommitCollection::from($commits)->firstMessage()
        );
    }

    /**
     * @test
     */
    public function messages(): void
    {
        $commits = [
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => $message1 = 'foo bar baz',
                    'committer' => [
                        'date' => '2020-01-01 19:00:00',
                    ],
                ],
            ]),
            Commit::fromResponse([
                'sha' => 'sha',
                'commit' => [
                    'message' => $message2 = 'foo bar',
                    'committer' => [
                        'date' => '2020-01-01 18:00:00',
                    ],
                ],
            ]),
        ];

        static::assertSame(
            [
                $message1,
                $message2,
            ],
            CommitCollection::from($commits)->messages()
        );
    }
}
