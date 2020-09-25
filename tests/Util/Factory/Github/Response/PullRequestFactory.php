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

use App\Tests\Util\Helper;

class PullRequestFactory
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

        $repo = null;
        if ($faker->boolean) {
            $repo = [
                'owner' => [
                    'login' => $faker->userName,
                ],
            ];
        }

        $response = [
            'number' => $faker->numberBetween(1, 99999),
            'title' => $faker->sentence,
            'updated_at' => $faker->date('Y-m-d H:i:s'),
            'base' => [
                'ref' => $faker->sentence(1),
            ],
            'head' => [
                'ref' => $faker->sentence(1),
                'sha' => $faker->sha256,
                'repo' => $repo,
            ],
            'user' => [
                'login' => $faker->userName,
                'html_url' => $faker->url,
            ],
            'mergeable' => $faker->optional()->boolean,
            'body' => sprintf(
                <<<'BODY'
<!-- %s -->

## Subject

%s

## Changelog

```markdown
### Changed
- The fourth argument of the `SetObjectFieldValueAction::__construct` method is now mandatory.
```
BODY,
                $faker->text,
                $faker->text
            ),
            'html_url' => $faker->url,
            'labels' => array_map(static function (): array {
                return LabelFactory::create();
            }, range(0, $faker->numberBetween(0, 5))),
        ];

        return array_replace_recursive(
            $response,
            $parameters
        );
    }
}
