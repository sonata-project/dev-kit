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

namespace App\Tests\Github\Domain\Value\IncomingWebhook;

use App\Github\Domain\Value\IncomingWebhook\Event;
use App\Github\Domain\Value\IncomingWebhook\Payload;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
    use Helper;

    /**
     * @test
     */
    public function fromJsonString(): void
    {
        $event = Event::fromString('issue_comment');

        $array = [
            'action' => $action = 'synchronize',
            'issue' => [
                'number' => $issue = 123,
                'user' => [
                    'id' => $issueAuthorId = 456,
                    'login' => 'Baz',
                    'html_url' => 'http://example.com',
                ],
            ],
            'comment' => [
                'id' => 123,
                'body' => 'comment body',
                'created_at' => self::faker()->date('Y-m-d\TH:i:s\Z'),
                'user' => [
                    'id' => 567,
                    'login' => 'FooBar',
                    'html_url' => 'http://example.com',
                ],
            ],
            'repository' => [
                'full_name' => $repository = 'sonata-project/SonataAdminBundle',
            ],
        ];

        $json = json_encode($array);

        $payload = Payload::fromJsonString($json, $event);

        self::assertSame($action, $payload->action()->toString());
        self::assertSame($issue, $payload->issue()->toInt());
        self::assertSame($issueAuthorId, $payload->issueAuthor()->id());
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
                'number' => $issue = 123,
                'user' => [
                    'id' => $issueAuthorId = 456,
                    'login' => 'FooBar',
                    'html_url' => 'http://example.com',
                ],
            ],
            'comment' => [
                'id' => 123,
                'body' => 'comment body',
                'created_at' => self::faker()->date('Y-m-d\TH:i:s\Z'),
                'user' => [
                    'id' => 456,
                    'login' => 'FooBar',
                    'html_url' => 'http://example.com',
                ],
            ],
            'repository' => [
                'full_name' => $repository = 'sonata-project/SonataAdminBundle',
            ],
        ];

        $json = json_encode($array);

        $payload = Payload::fromJsonString($json, $event);

        self::assertSame($action, $payload->action()->toString());
        self::assertSame($issue, $payload->issue()->toInt());
        self::assertSame($issueAuthorId, $payload->issueAuthor()->id());
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
                'number' => $issue = 123,
                'user' => [
                    'id' => $issueAuthorId = 456,
                    'login' => 'FooBar',
                    'html_url' => 'http://example.com',
                ],
            ],
            'comment' => [
                'id' => 123,
                'body' => 'comment body',
                'created_at' => self::faker()->date('Y-m-d\TH:i:s\Z'),
                'user' => [
                    'id' => 789,
                    'login' => 'Baz',
                    'html_url' => 'http://example.com',
                ],
            ],
            'repository' => [
                'full_name' => $repository = 'sonata-project/SonataAdminBundle',
            ],
        ];

        $json = json_encode($array);

        $payload = Payload::fromJsonString($json, $event);

        self::assertSame($action, $payload->action()->toString());
        self::assertSame($issue, $payload->issue()->toInt());
        self::assertSame($issueAuthorId, $payload->issueAuthor()->id());
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
                'number' => $issue = 123,
                'user' => [
                    'id' => $issueAuthorId = 456,
                    'login' => 'Baz',
                    'html_url' => 'http://example.com',
                ],
            ],
            'repository' => [
                'full_name' => $repository = 'sonata-project/SonataAdminBundle',
            ],
        ];

        $json = json_encode($array);

        $payload = Payload::fromJsonString($json, $event);

        self::assertSame($action, $payload->action()->toString());
        self::assertSame($issue, $payload->issue()->toInt());
        self::assertSame($issueAuthorId, $payload->issueAuthor()->id());
        self::assertFalse($payload->isTheCommentFromTheAuthor());
        self::assertSame($repository, $payload->repository()->toString());
    }
}
