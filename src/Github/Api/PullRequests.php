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
use App\Github\Domain\Value\PullRequest;
use Github\Client as GithubClient;
use Github\ResultPagerInterface;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class PullRequests
{
    private GithubClient $github;
    private ResultPagerInterface $githubPager;

    public function __construct(GithubClient $github, ResultPagerInterface $githubPager)
    {
        $this->github = $github;
        $this->githubPager = $githubPager;
    }

    /**
     * @return PullRequest[]
     */
    public function all(Repository $repository, array $params = []): array
    {
        return array_map(function (array $listResponse) use ($repository): PullRequest {
            $issue = Issue::fromInt($listResponse['number']);

            $detailResponse = $this->github->pullRequests()->show(
                $repository->username(),
                $repository->name(),
                $issue->toInt()
            );

            return PullRequest::fromDetailResponse($detailResponse);
        }, $this->github->pullRequests()->all($repository->username(), $repository->name(), $params));
    }

    public function create(Repository $repository, string $title, string $head, string $base, string $body = ''): void
    {
        $this->github->pullRequests()->create(
            $repository->username(),
            $repository->name(),
            [
                'title' => $title,
                'head' => $head,
                'base' => $base,
                'body' => $body,
            ]
        );
    }

    public function merge(Repository $repository, PullRequest $pullRequest, bool $squash, ?string $title = null): void
    {
        $this->github->pullRequests()->merge(
            $repository->username(),
            $repository->name(),
            $pullRequest->issue()->toInt(),
            $squash ? '' : $pullRequest->title(),
            $pullRequest->head()->sha(),
            $squash,
            $title
        );
    }

    public function hasOpenPullRequest(Repository $repository, string $head): bool
    {
        return 0 !== $this->all(
            $repository,
            [
                'state' => 'open',
                'head' => $head,

            ]
        );
    }
}
