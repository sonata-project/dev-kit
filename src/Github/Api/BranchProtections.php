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

use App\Domain\Value\Branch;
use App\Domain\Value\Repository;
use Github\Client as GithubClient;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class BranchProtections
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    /**
     * @param mixed[] $params
     */
    public function update(Repository $repository, Branch $branch, array $params): void
    {
        $this->github->repo()->protection()->update(
            $repository->username(),
            $repository->name(),
            $branch->name(),
            $params
        );
    }
}
