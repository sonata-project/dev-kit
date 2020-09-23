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

namespace App\Tests\Util\Factory;

use App\Tests\Util\Helper;
use function Symfony\Component\String\u;

final class CombinedStatusResponseFactory
{
    use Helper;

    /**
     * @param array<mixed> $parameters
     *
     * @return array<mixed>
     */
    public static function create(array $parameters = []): array
    {
        $faker = self::faker();

        $response = [
            'state' => $faker->randomElement([
                'failure',
                'pending',
                'success',
            ]),
            'statuses' => array_map(static function (): array {
                return StatusResponseFactory::create();
            }, range(0, $faker->numberBetween(0, 3))),
        ];

        if ([] === $parameters) {
            return $response;
        }

        return array_replace_recursive(
            $response,
            $parameters
        );
    }
}
