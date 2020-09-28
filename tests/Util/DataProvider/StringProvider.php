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

use App\Tests\Util\Helper;

final class StringProvider
{
    use Helper;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function arbitrary(): \Generator
    {
        yield 'string-arbitrary' => [self::faker()->sentence];
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function blank(): \Generator
    {
        yield 'string-blank' => [' '];
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function empty(): \Generator
    {
        yield 'string-empty' => [''];
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function lengthGreaterThan256Characters(): \Generator
    {
        yield 'string-longer-than-256-characters' => [self::stringWithLength(257)];
    }

    /**
     * @return \Generator<string, array<string>>
     */
    public static function untrimmed(): \Generator
    {
        $faker = self::faker();

        $characters = [
            'newline' => PHP_EOL,
            'space' => ' ',
            'tab' => "\t",
        ];

        foreach ($characters as $name => $character) {
            $key = sprintf(
                'string-untrimmed-%s',
                $name
            );

            yield $key => [
                sprintf(
                    '%s%s%s',
                    str_repeat($character, 3),
                    $faker->sentence(3),
                    str_repeat($character, 3),
                ),
            ];
        }
    }

    private static function stringWithLength(int $length): string
    {
        $faker = self::faker();

        return str_pad(
            substr(
                $faker->sentence,
                0,
                $length
            ),
            $length,
            $faker->randomLetter
        );
    }
}
