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

namespace App\Tests\Github\Domain\Value\PullRequest\Head\Repo;

use App\Github\Domain\Value\PullRequest\Head\Repo\Owner;
use PHPUnit\Framework\TestCase;

final class OwnerTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfRepsonseIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Owner::fromResponse([]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRepsonseArrayDoesNotContainKeyLogin(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Owner::fromResponse([
            'foo' => 'bar',
        ]);
    }

    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Owner::fromResponse([
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
            Owner::fromResponse($response)->login()
        );
    }
}
