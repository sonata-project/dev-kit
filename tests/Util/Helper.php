<?php

declare(strict_types=1);

namespace App\Tests\Util;

use Faker\Factory;
use Faker\Generator;

trait Helper
{
    final protected static function faker(string $locale = 'en_US'): Generator
    {
        static $fakers = [];

        if (!\array_key_exists($locale, $fakers)) {
            $faker = Factory::create($locale);

            $faker->seed(9001);

            $fakers[$locale] = $faker;
        }

        return $fakers[$locale];
    }
}
