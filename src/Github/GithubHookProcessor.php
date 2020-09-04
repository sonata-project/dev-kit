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

namespace App\Github;

use App\Github\Domain\Value\Event;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class GithubHookProcessor
{
    /**
     * @var GithubClient
     */
    private $client;

    public function __construct(GithubClient $client)
    {
        $this->client = $client;
    }

    /**
     * Removes the "Pending Author" label if the author respond on an issue or pull request.
     *
     * Github events: issue_comment, pull_request_review_comment
     */
    public function processPendingAuthor(Event $event, array $payload): void
    {
        if (!\in_array($payload['action'], ['created', 'synchronize'], true)) {
            return;
        }

        $issueKey = 'issue_comment' === $event->toString() ? 'issue' : 'pull_request';

        list($repoUser, $repoName) = explode('/', $payload['repository']['full_name']);
        $issueId = $payload[$issueKey]['number'];
        $issueAuthorId = $payload[$issueKey]['user']['id'];
        // If it's a PR synchronization, it's obviously done from the author.
        $commentAuthorId = 'synchronize' === $payload['action'] ? $issueAuthorId : $payload['comment']['user']['id'];

        if ($commentAuthorId === $issueAuthorId) {
            $this->client->removeIssueLabel(
                $repoUser,
                $repoName,
                (int) $issueId,
                'pending author'
            );
        }
    }

    /**
     * Manages RTM label.
     *
     * - If a PR is updated and 'RTM' is set, it is removed.
     */
    public function processReviewLabels(Event $event, array $payload): void
    {
        if (!\in_array($payload['action'], ['opened', 'synchronize'], true)) {
            return;
        }

        list($repoUser, $repoName) = explode('/', $payload['repository']['full_name']);

        if ('synchronize' === $payload['action']) {
            $this->client->removeIssueLabel(
                $repoUser,
                $repoName,
                (int) $payload['number'],
                'RTM'
            );
        }
    }
}
