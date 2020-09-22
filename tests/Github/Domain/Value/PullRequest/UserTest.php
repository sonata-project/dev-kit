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

use App\Github\Domain\Value\PullRequest\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfRepsonseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::fromResponse([]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayDoesNotContainKeyLogin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::fromResponse([
            'foo' => 'bar',
            'html_url' => 'https://test.com',
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayDoesNotContainKeyHtmlUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::fromResponse([
            'login' => 'bar',
            'foo' => 'https://test.com',
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfLoginIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::fromResponse([
            'login' => '',
            'html_url' => 'https://test.com',
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfHtmlUrlIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::fromResponse([
            'login' => 'foo',
            'html_url' => '',
        ]);
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $response = [
            'login' => $login = 'foo-bar',
            'html_url' => $htmlUrl = 'https://test.com',
        ];

        $user = User::fromResponse($response);

        self::assertSame(
            $login,
            $user->login()
        );
        self::assertSame(
            $htmlUrl,
            $user->htmlUrl()
        );
    }
}
