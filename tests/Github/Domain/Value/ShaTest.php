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
use App\Tests\Util\Helper;
use PHPUnit\Framework\TestCase;

final class ShaTest extends TestCase
{
    use Helper;

    /**
     * @test
     *
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::blank()
     * @dataProvider \App\Tests\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionFor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Sha::fromString($value);
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $value = self::faker()->sha256;

        $sha = Sha::fromString($value);

        self::assertSame($value, $sha->toString());
    }
}
