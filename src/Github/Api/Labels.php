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
use App\Github\Domain\Value\Label;
use Github\Client as GithubClient;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Labels
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    /**
     * @return Label[]
     */
    public function all(Repository $repository): array
    {
        return array_map(static function (array $response): Label {
            return Label::fromResponse($response);
        }, $this->github->repo()->labels()->all($repository->vendor(), $repository->name()));
    }

    public function create(Repository $repository, array $params): void
    {
        $this->github->repo()->labels()->create(
            $repository->vendor(),
            $repository->name(),
            $params
        );
    }

    public function update(Repository $repository, Label $label, array $params): void
    {
        $this->github->repo()->labels()->update(
            $repository->vendor(),
            $repository->name(),
            $label->name(),
            $params
        );
    }

    public function remove(Repository $repository, Label $label): void
    {
        $this->github->repo()->labels()->remove(
            $repository->vendor(),
            $repository->name(),
            $label->name()
        );
    }
}
