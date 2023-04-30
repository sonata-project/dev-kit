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
use App\Github\Domain\Value\Issue;
use App\Github\Domain\Value\Label;
use Github\Client as GithubClient;
use Github\Exception\RuntimeException;

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

    /**
     * @param array<Label> $labels
     */
    public function addLabels(Repository $repository, Issue $issue, array $labels): void
    {
        $this->github->issues()->labels()->add(
            $repository->username(),
            $repository->name(),
            $issue->toInt(),
            array_map(
                static fn (Label $label): string => $label->name(),
                $labels
            )
        );
    }

    public function removeLabel(Repository $repository, Issue $issue, Label $label): void
    {
        try {
            $this->github->issues()->labels()->remove(
                $repository->username(),
                $repository->name(),
                $issue->toInt(),
                $label->name()
            );
        } catch (RuntimeException $e) {
            if (404 !== $e->getCode()) {
                throw $e;
            }
        }
    }
}
