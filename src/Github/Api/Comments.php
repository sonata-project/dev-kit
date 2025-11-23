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
use App\Github\Domain\Value\Comment;
use App\Github\Domain\Value\Issue;
use App\Github\Domain\Value\PullRequest;
use Github\Client as GithubClient;
use Github\ResultPagerInterface;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Comments
{
    public function __construct(private GithubClient $github, private ResultPagerInterface $githubPager)
    {
    }

    /**
     * @return Comment[]
     */
    public function all(Repository $repository, PullRequest $pullRequest, ?string $username = null): array
    {
        $comments = array_map(
            Comment::fromResponse(...),
            $this->githubPager->fetchAll($this->github->issues()->comments(), 'all', [
                $repository->username(),
                $repository->name(),
                $pullRequest->issue()->toInt(),
            ])
        );

        if (null === $username) {
            return $comments;
        }

        return array_filter(
            $comments,
            static fn (Comment $comment): bool => $comment->author()->login() === $username
        );
    }

    public function lastComment(Repository $repository, PullRequest $pullRequest, ?string $username = null): ?Comment
    {
        $allComments = $this->all($repository, $pullRequest, $username);

        $lastComment = end($allComments);

        if (!$lastComment instanceof Comment) {
            return null;
        }

        return $lastComment;
    }

    public function create(Repository $repository, Issue $issue, string $message): void
    {
        Assert::stringNotEmpty($message);

        $this->github->issues()->comments()->create(
            $repository->username(),
            $repository->name(),
            $issue->toInt(),
            [
                'body' => $message,
            ]
        );
    }

    public function remove(Repository $repository, Comment $comment): void
    {
        $this->github->issues()->comments()->remove(
            $repository->username(),
            $repository->name(),
            $comment->id()
        );
    }
}
