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
use App\Github\Domain\Value\CombinedStatus;
use App\Github\Domain\Value\Sha;
use Github\Client as GithubClient;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Statuses
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    public function combined(Repository $repository, Sha $sha): CombinedStatus
    {
        $response = $this->github->repos()->statuses()->combined(
            $repository->username(),
            $repository->name(),
            $sha->toString()
        );

        return CombinedStatus::fromResponse($response);
    }
}
