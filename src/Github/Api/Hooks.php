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
use App\Github\Domain\Value\Hook;
use Github\Client as GithubClient;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Hooks
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    /**
     * @return Hook[]
     */
    public function all(Repository $repository): array
    {
        return array_map(
            static fn (array $response): Hook => Hook::fromResponse($response),
            $this->github->repo()->hooks()->all($repository->username(), $repository->name())
        );
    }

    /**
     * @param mixed[] $params
     */
    public function create(Repository $repository, array $params): void
    {
        $this->github->repo()->hooks()->create(
            $repository->username(),
            $repository->name(),
            $params
        );
    }

    /**
     * @param mixed[] $params
     */
    public function update(Repository $repository, Hook $hook, array $params): void
    {
        $this->github->repo()->hooks()->update(
            $repository->username(),
            $repository->name(),
            $hook->id(),
            $params
        );
    }

    public function remove(Repository $repository, Hook $hook): void
    {
        $this->github->repo()->hooks()->remove(
            $repository->username(),
            $repository->name(),
            $hook->id()
        );
    }

    public function ping(Repository $repository, Hook $hook): void
    {
        $this->github->repo()->hooks()->ping(
            $repository->username(),
            $repository->name(),
            $hook->id()
        );
    }
}
