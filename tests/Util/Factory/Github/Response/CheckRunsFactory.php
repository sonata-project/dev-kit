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

final class CheckRunsFactory
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
            'check_runs' => array_map(static function (): array {
                return CheckRunFactory::create();
            }, range(0, $faker->numberBetween(2, 3))),
        ];

        if (\array_key_exists('check_runs', $parameters)) {
            $response['check_runs'] = $parameters['check_runs'];
        }

        return array_replace_recursive(
            $response,
            $parameters
        );
    }
}
