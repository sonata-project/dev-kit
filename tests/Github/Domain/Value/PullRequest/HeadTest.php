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
use App\Tests\Util\Factory\Github\Response\UserFactory;
use PHPUnit\Framework\TestCase;

final class HeadTest extends TestCase
{
    public function testThrowsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([]);
    }

    public function testThrowsExceptionIfResponseArrayDoesNotContainKeyRef(): void
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

    public function testThrowsExceptionIfResponseArrayContainKeyRefButEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
            'ref' => '',
            'sha' => 'sha',
            'repo' => [
                'owner' => UserFactory::create(),
            ],
        ]);
    }

    public function testThrowsExceptionIfResponseArrayDoesNotContainKeySha(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
            'ref' => 'ref',
            'foo' => 'bar',
            'repo' => [
                'owner' => UserFactory::create(),
            ],
        ]);
    }

    public function testThrowsExceptionIfResponseArrayContainKeyShaButEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
            'ref' => 'ref',
            'sha' => '',
            'repo' => [
                'owner' => UserFactory::create(),
            ],
        ]);
    }

    public function testThrowsExceptionIfResponseArrayDoesNotContainKeyRepo(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
            'ref' => 'ref',
            'sha' => 'sha',
            'foo' => [
                'owner' => UserFactory::create(),
            ],
        ]);
    }

    public function testThrowsExceptionIfResponseArrayContainKeyRepoButEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Head::fromResponse([
            'ref' => 'ref',
            'sha' => 'sha',
            'repo' => [],
        ]);
    }

    public function testValid(): void
    {
        $response = [
            'ref' => $ref = 'foo',
            'sha' => $sha = 'sha',
            'repo' => [
                'owner' => UserFactory::create(),
            ],
        ];

        $head = Head::fromResponse($response);

        static::assertSame($ref, $head->ref());
        static::assertSame($sha, $head->sha()->toString());
    }
}
