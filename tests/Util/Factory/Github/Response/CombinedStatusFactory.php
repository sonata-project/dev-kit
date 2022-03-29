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

namespace App\Tests\Util\Factory\Github\Response;

use Ergebnis\Test\Util\Helper;

final class CombinedStatusFactory
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

        $state = $faker->randomElement([
            'failure',
            'pending',
            'success',
        ]);

        $response = [
            'state' => $state,
            'statuses' => array_map(
                static fn (): array => StatusFactory::create(),
                range('pending' === $state ? 0 : 1, $faker->numberBetween(2, 3))
            ),
        ];

        if (\array_key_exists('statuses', $parameters)) {
            $response['statuses'] = $parameters['statuses'];
        }

        return array_replace_recursive(
            $response,
            $parameters
        );
    }
}
