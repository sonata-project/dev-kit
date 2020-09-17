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
    public function throwsExceptionIfRepsonseArrayDoesNotContainKeyState(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::fromResponse([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::fromResponse([
            'login' => '',
        ]);
    }

    /**
     * @test
     */
    public function login(): void
    {
        $response = [
            'login' => $value = 'foo-bar',
        ];

        self::assertSame(
            $value,
            User::fromResponse($response)->login()
        );
    }
}
