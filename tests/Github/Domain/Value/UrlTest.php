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

use App\Github\Domain\Value\Url;
use Ergebnis\Test\Util\Helper;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    use Helper;

    /**
     * @test
     *
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::blank()
     * @dataProvider \Ergebnis\Test\Util\DataProvider\StringProvider::empty()
     */
    public function throwsExceptionFor(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Url::fromString($value);
    }

    /**
     * @test
     */
    public function throwsExceptionIfValueDoesNotStartWithHttp(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Url::fromString('://example.com');
    }

    /**
     * @test
     */
    public function valid(): void
    {
        $value = self::faker()->url;

        $url = Url::fromString($value);

        self::assertSame($value, $url->toString());
    }
}
