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
use function Symfony\Component\String\u;

final class LabelFactory
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
            'name' => $faker->word,
            'color' => u($faker->hexColor)->replace('#', '')->toString(),
        ];

        return array_replace_recursive(
            $response,
            $parameters
        );
    }
}
