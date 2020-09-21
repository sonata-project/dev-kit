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
use Github\Client as GithubClient;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Repositories
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    public function show(Repository $repository): array
    {
        return $this->github->repo()->show(
            $repository->username(),
            $repository->name()
        );
    }

    public function update(Repository $repository, array $params): void
    {
        $this->github->repo()->update(
            $repository->username(),
            $repository->name(),
            array_merge(
                $params,
                [
                    'name' => $repository->name(),
                ]
            )
        );
    }

    public function merge(Repository $repository, string $base, string $head): bool
    {
        Assert::stringNotEmpty($base);
        Assert::stringNotEmpty($head);

        $response = $this->github->repo()->merge(
            $repository->username(),
            $repository->name(),
            $base,
            $head
        );

        if (\is_array($response) && \array_key_exists('sha', $response)) {
            return true;
        }

        return false;
    }
}
