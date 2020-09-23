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

namespace App\Tests\Util;

use App\Github\Domain\Value\Status;
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

    public static function assertStatusEqualsStatus(Status $expected, Status $other): void
    {
        self::assertSame($expected->state(), $other->state());
        self::assertSame($expected->description(), $other->description());
        self::assertSame($expected->targetUrl(), $other->targetUrl());
    }
}
