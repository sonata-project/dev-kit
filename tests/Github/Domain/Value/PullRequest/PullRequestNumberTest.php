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

use App\Github\Domain\Value\PullRequest\PullRequestNumber;
use PHPUnit\Framework\TestCase;

final class PullRequestNumberTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PullRequestNumber::fromInt(0);
    }

    /**
     * @test
     */
    public function throwsExceptionIfValueIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PullRequestNumber::fromInt(-1);
    }

    /**
     * @test
     */
    public function fromInt(): void
    {
        $value = 123;

        self::assertSame(
            $value,
            PullRequestNumber::fromInt($value)->toInt()
        );
    }

    /**
     * @test
     */
    public function fromResponse(): void
    {
        $response = [
            'number' => $value = 123,
        ];

        self::assertSame(
            $value,
            PullRequestNumber::fromResponse($response)->toInt()
        );
    }
}
