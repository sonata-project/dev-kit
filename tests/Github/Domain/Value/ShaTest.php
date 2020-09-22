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

namespace App\Tests\Github\Domain\Value;

use App\Github\Domain\Value\Sha;
use PHPUnit\Framework\TestCase;

final class ShaTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Sha::fromString('');
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $sha = Sha::fromString('abc');

        self::assertSame('abc', $sha->toString());
    }
}
