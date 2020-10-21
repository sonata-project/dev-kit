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

final class CheckRunFactory
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
            'status' => $faker->randomElement([
                'completed',
                'in_progress',
                'queued',
            ]),
            'conclusion' => $faker->randomElement([
                'action_required',
                'cancelled',
                'failure',
                'neutral',
                'skipped',
                'success',
                'timed_out',
            ]),
            'name' => $faker->word,
            'details_url' => $faker->url,
        ];

        return array_replace_recursive(
            $response,
            $parameters
        );
    }
}
