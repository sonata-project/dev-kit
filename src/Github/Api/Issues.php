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
use App\Github\Domain\Value\Label;
use Github\Client as GithubClient;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Issues
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    public function addLabel(Repository $repository, IssueId $issueId, Label $label): void
    {
        $this->github->issues()->labels()->add(
            $repository->vendor(),
            $repository->name(),
            $issueId->toInt(),
            $label->name()
        );
    }

    public function removeLabel(Repository $repository, IssueId $issueId, Label $label): void
    {
        $this->github->issues()->labels()->remove(
            $repository->vendor(),
            $repository->name(),
            $issueId->toInt(),
            $label->name()
        );
    }
}
