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

namespace App\Tests\Domain\Value;

use App\Domain\Value\TrimmedNonEmptyString;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class TrimmedNonEmptyStringTest extends TestCase
{
    use Helper;

    public function testFromString(): void
    {
        $string = self::faker()->word();

        static::assertSame(
            $string,
            TrimmedNonEmptyString::fromString($string)->toString()
        );
    }

    public function testFromStringWithUntrimmedValue(): void
    {
        $string = self::faker()->word();
        $untrimmed = ' '.$string.' ';

        static::assertSame(
            $string,
            TrimmedNonEmptyString::fromString($untrimmed)->toString()
        );
    }

    public function testFromStringWithEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TrimmedNonEmptyString::fromString('');
    }

    public function testFromStringWithBlankValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TrimmedNonEmptyString::fromString(' ');
    }
}
