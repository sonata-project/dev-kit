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

namespace App\Github\Action;

use App\Github\Domain\Value\Issue\IssueId;
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\Repository;
use Github\Client as GithubClient;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 *
 * Adds a label from an issue if this one is not set.
 */
final class AddIssueLabel
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    public function __invoke(Repository $repository, IssueId $issueId, Label $label): void
    {
        foreach ($this->github->issues()->labels()->all($repository->username(), $repository->name(), $issueId->toInt()) as $response) {
            if ($label->equals(Label::fromResponse($response))) {
                return;
            }
        }

        $this->github->issues()->labels()->add(
            $repository->username(),
            $repository->name(),
            $issueId->toInt(),
            $label->name()
        );
    }
}
