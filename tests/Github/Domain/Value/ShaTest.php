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
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class ShaTest extends TestCase
{
    use Helper;

    /**
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
     */
    public function testThrowsExceptionFor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Sha::fromString($value);
    }

    public function testValid(): void
    {
        $value = self::faker()->sha256();

        $sha = Sha::fromString($value);

        static::assertSame($value, $sha->toString());
    }
}
