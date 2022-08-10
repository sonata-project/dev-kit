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

use App\Github\Domain\Value\Commit;
use PHPUnit\Framework\TestCase;

final class CommitTest extends TestCase
{
    public function testThrowsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Commit::fromResponse([]);
    }

    public function testValid(): void
    {
        $response = [
            'sha' => $sha = 'sha',
            'commit' => [
                'message' => $message = 'foo bar baz',
                'committer' => [
                    'date' => $date = '2020-01-01 19:00:00',
                ],
            ],
        ];

        $commit = Commit::fromResponse($response);

        static::assertSame($sha, $commit->sha()->toString());
        static::assertSame($message, $commit->message());
        static::assertSame(
            (new \DateTimeImmutable($date))->getTimestamp(),
            $commit->date()->getTimestamp()
        );
    }
}
