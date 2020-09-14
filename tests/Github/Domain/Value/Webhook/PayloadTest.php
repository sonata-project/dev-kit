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

namespace App\Tests\Github\Domain\Value\Webhook;

use App\Github\Domain\Value\Webhook\Action;
use App\Github\Domain\Value\Webhook\Event;
use App\Github\Domain\Value\Webhook\Payload;
use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
    /**
     * @test
     */
    public function fromJsonString(): void
    {
        $event = Event::fromString('issue_comment');

        $array = [
            'action' => $action = 'synchronize',
            'issue' => [
                'number' => $issueId = 123,
                'user' => [
                    'id' => $issueAuthorId = 456,
                ],
            ],
            'comment' => [
                'user' => [
                    'id' => 567,
                ],
            ],
            'repository' => [
                'full_name' => $repository = 'sonata-project/SonataAdminBundle',
            ],
        ];

        $json = json_encode($array);

        $payload = Payload::fromJsonString($json, $event);

        self::assertSame($action, $payload->action());
        self::assertSame($issueId, $payload->issueId());
        self::assertSame($issueAuthorId, $payload->issueAuthorId());
        self::assertTrue($payload->isTheCommentFromTheAuthor());
        self::assertSame($repository, $payload->repository()->toString());
    }

    /**
     * @test
     */
    public function thatTheCommentIsFromTheAuthorBecauseOfSameUserIdsAndAnActionDifferentToSynchronize(): void
    {
        $event = Event::fromString('issue_comment');

        $array = [
            'action' => $action = 'foo',
            'issue' => [
                'number' => $issueId = 123,
                'user' => [
                    'id' => $issueAuthorId = 456,
                ],
            ],
            'comment' => [
                'user' => [
                    'id' => 456,
                ],
            ],
            'repository' => [
                'full_name' => $repository = 'sonata-project/SonataAdminBundle',
            ],
        ];

        $json = json_encode($array);

        $payload = Payload::fromJsonString($json, $event);

        self::assertSame($action, $payload->action());
        self::assertSame($issueId, $payload->issueId());
        self::assertSame($issueAuthorId, $payload->issueAuthorId());
        self::assertTrue($payload->isTheCommentFromTheAuthor());
        self::assertSame($repository, $payload->repository()->toString());
    }

    /**
     * @test
     */
    public function thatTheCommentIsNotFromTheAuthorBecauseOfDifferentUserIdsAndAnActionDifferentToSynchronize(): void
    {
        $event = Event::fromString('issue_comment');

        $array = [
            'action' => $action = 'foo',
            'issue' => [
                'number' => $issueId = 123,
                'user' => [
                    'id' => $issueAuthorId = 456,
                ],
            ],
            'comment' => [
                'user' => [
                    'id' => 789,
                ],
            ],
            'repository' => [
                'full_name' => $repository = 'sonata-project/SonataAdminBundle',
            ],
        ];

        $json = json_encode($array);

        $payload = Payload::fromJsonString($json, $event);

        self::assertSame($action, $payload->action());
        self::assertSame($issueId, $payload->issueId());
        self::assertSame($issueAuthorId, $payload->issueAuthorId());
        self::assertFalse($payload->isTheCommentFromTheAuthor());
        self::assertSame($repository, $payload->repository()->toString());
    }

    /**
     * @test
     */
    public function thatCommentDoesNotNeedToBeSetInPayload(): void
    {
        $event = Event::fromString('issue_comment');

        $array = [
            'action' => $action = 'foo',
            'issue' => [
                'number' => $issueId = 123,
                'user' => [
                    'id' => $issueAuthorId = 456,
                ],
            ],
            'repository' => [
                'full_name' => $repository = 'sonata-project/SonataAdminBundle',
            ],
        ];

        $json = json_encode($array);

        $payload = Payload::fromJsonString($json, $event);

        self::assertSame(Action::fromString($action), $payload->action());
        self::assertSame($issueId, $payload->issueId());
        self::assertSame($issueAuthorId, $payload->issueAuthorId());
        self::assertFalse($payload->isTheCommentFromTheAuthor());
        self::assertSame($repository, $payload->repository()->toString());
    }
}
