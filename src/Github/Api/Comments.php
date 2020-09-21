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
use App\Github\Domain\Value\Issue\IssueId;
use Github\Client as GithubClient;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Comments
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    public function create(Repository $repository, IssueId $issueId, string $message): void
    {
        Assert::stringNotEmpty($message);

        $this->github->repo()->comments()->create(
            $repository->vendor(),
            $repository->name(),
            $issueId->toInt(),
            [
                'body' => $message,
            ]
        );
    }
}
