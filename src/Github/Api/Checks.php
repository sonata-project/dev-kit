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
use App\Github\Domain\Value\CheckRuns;
use App\Github\Domain\Value\Sha;
use Github\Client as GithubClient;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Checks
{
    public function __construct(private GithubClient $github)
    {
    }

    public function all(Repository $repository, Sha $sha): CheckRuns
    {
        $response = $this->github->repo()->checkRuns()->allForReference(
            $repository->username(),
            $repository->name(),
            $sha->toString()
        );

        return CheckRuns::fromResponse($response);
    }
}
