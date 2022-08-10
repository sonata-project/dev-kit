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
    public function testThrowsExceptionIfValueIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CommitCollection::from([]);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testThrowsExceptionIfValueIsNotEmptyButNotAllInstanceOfCommitClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // @phpstan-ignore-next-line
        CommitCollection::from(['foo']);
    }

    public function testImplementsCountable(): void
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

    public function testCountByMethodAndInterface(): void
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

    public function testUniqueCount(): void
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

    public function testFirstMessage(): void
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

    public function testMessages(): void
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
