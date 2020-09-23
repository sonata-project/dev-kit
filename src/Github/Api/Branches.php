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

namespace App\Github\Api;

use App\Domain\Value\Repository;
use App\Github\Domain\Value\Branch;
use Github\Client as GithubClient;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Branches
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    /**
     * @return Branch[]
     */
    public function all(Repository $repository): array
    {
        return array_map(static function ($response): Branch {
            return Branch::fromResponse($response);
        }, $this->github->repos()->branches($repository->username(), $repository->name()));
    }

    public function get(Repository $repository, string $name): Branch
    {
        $response = $this->github->repo()->branches(
            $repository->username(),
            $repository->name(),
            $name
        );

        return Branch::fromResponse($response);
    }
}
