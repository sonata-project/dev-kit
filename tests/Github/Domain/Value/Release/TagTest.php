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
use Ergebnis\Test\Util\DataProvider\StringProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final class TagTest extends TestCase
{
    #[DataProviderExternal(StringProvider::class, 'blank')]
    #[DataProviderExternal(StringProvider::class, 'empty')]
    public function testThrowsExceptionFor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Tag::fromString($value);
    }

    public function testValid(): void
    {
        $tag = Tag::fromString('1.1.0');

        static::assertSame('1.1.0', $tag->toString());
    }
}
