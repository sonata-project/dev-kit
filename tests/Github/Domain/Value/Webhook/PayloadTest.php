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
        $event = Event::fromString('issue');

        $array = [
            'action' => $action = 'synchonize',
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

        self::assertSame($event, $payload->event());
        self::assertSame($action, $payload->action());
        self::assertSame($issueId, $payload->issueId());
        self::assertSame($issueAuthorId, $payload->issueAuthorId());
        self::assertSame($payload->issueAuthorId(), $payload->commentAuthorId());
        self::assertSame($repository, $payload->repository()->toString());
    }
}
