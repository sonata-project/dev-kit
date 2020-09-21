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
use App\Github\Domain\Value\PullRequest;
use Github\Client as GithubClient;
use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class References
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    public function remove(Repository $repository, PullRequest $pullRequest): void
    {
        $reference = u('heads/')
            ->append($pullRequest->head()->ref())
            ->toString();

        $this->github->gitData()->references()->remove(
            $repository->username(),
            $repository->name(),
            $reference
        );
    }
}
