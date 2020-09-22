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

namespace App\Tests\Github\Domain\Value\Release;

use App\Github\Domain\Value\Release\Tag;
use PHPUnit\Framework\TestCase;

final class TagTest extends TestCase
{
    /**
     * @test
     */
    public function throwsExceptionIfValueIsEmptyString(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Tag::fromString('');
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $tag = Tag::fromString('1.1.0');

        self::assertSame('1.1.0', $tag->toString());
    }
}
