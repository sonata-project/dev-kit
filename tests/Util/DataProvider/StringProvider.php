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

namespace App\Tests\Util\DataProvider;

use Ergebnis\Test\Util\Helper;

final class StringProvider
{
    use Helper;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function lengthGreaterThan256Characters(): \Generator
    {
        yield 'string-longer-than-256-characters' => [self::stringWithLength(257)];
    }

    private static function stringWithLength(int $length): string
    {
        $faker = self::faker();

        return str_pad(
            substr(
                $faker->sentence(),
                0,
                $length
            ),
            $length,
            $faker->randomLetter()
        );
    }
}
