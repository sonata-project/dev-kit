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

namespace App\Tests\Github\Domain\Value\Issue;

use App\Github\Domain\Value\Issue;
use PHPUnit\Framework\TestCase;

final class IssueTest extends TestCase
{
    public function testThrowsExceptionIfValueIsZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Issue::fromInt(0);
    }

    public function testThrowsExceptionIfValueIsNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Issue::fromInt(-1);
    }

    public function testFromInt(): void
    {
        $value = 123;

        static::assertSame(
            $value,
            Issue::fromInt($value)->toInt()
        );

        static::assertSame(
            sprintf(
                '#%s',
                $value
            ),
            Issue::fromInt($value)->toString()
        );
    }
}
