<?php

declare(strict_types=1);

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
}
