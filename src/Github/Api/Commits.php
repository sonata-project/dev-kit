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
use App\Github\Domain\Value\Commit;
use App\Github\Domain\Value\PullRequest;
use Github\Client as GithubClient;
use Github\ResultPagerInterface;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Commits
{
    private GithubClient $github;
    private ResultPagerInterface $githubPager;

    public function __construct(GithubClient $github, ResultPagerInterface $githubPager)
    {
        $this->github = $github;
        $this->githubPager = $githubPager;
    }

    /**
     * @return Commit[]
     */
    public function all(Repository $repository, PullRequest $pullRequest): array
    {
        return array_map(static function (array $response): Commit {
            return Commit::fromResponse($response);
        }, $this->githubPager->fetchAll($this->github->pullRequest(), 'commits', [
            $repository->username(),
            $repository->name(),
            $pullRequest->issue()->toInt(),
        ]));
    }

    public function lastCommit(Repository $repository, PullRequest $pullRequest): Commit
    {
        $allCommits = $this->all($repository, $pullRequest);

        return end($allCommits);
    }

    /**
     * @return array<mixed>
     */
    public function compare(Repository $repository, Branch $branch, Branch $branchToCompare): array
    {
        return $this->github->repos()->commits()->compare(
            $repository->username(),
            $repository->name(),
            $branch->name(),
            $branchToCompare->name()
        );
    }
}
