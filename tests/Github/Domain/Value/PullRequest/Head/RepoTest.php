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

namespace App\Tests\Github\Domain\Value\PullRequest\Head;

use App\Github\Domain\Value\PullRequest\Head\Repo;
use PHPUnit\Framework\TestCase;

final class RepoTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfResponseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfResponseArrayDoesNotContainKeyOwner(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Repo::fromResponse([
            'owner' => [],
        ]);
    }

    /**
     * @test
     */
    public function owner(): void
    {
        $response = [
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
