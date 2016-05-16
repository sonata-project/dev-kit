<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DevKit;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class GithubHookProcessor
{
    /**
     * @var \Github\Client
     */
    private $githubClient;

    /**
     * @param string|null $githubAuthKey
     */
    public function __construct($githubAuthKey = null)
    {
        $this->githubClient = new \Github\Client();

        if ($githubAuthKey) {
            $this->githubClient->authenticate($githubAuthKey, null, \Github\Client::AUTH_HTTP_TOKEN);
        }
    }

    /**
     * Removes the "Pending Author" label if the author respond on an issue or pull request.
     *
     * Github events: issue_comment, pull_request_review_comment
     *
     * @param string $eventName
     * @param array  $payload
     */
    public function processPendingAuthor($eventName, array $payload)
    {
        if ('created' !== $payload['action']) {
            return;
        }

        $issueKey = 'issue_comment' === $eventName ? 'issue' : 'pull_request';

        list($repoUser, $repoName) = explode('/', $payload['repository']['full_name']);
        $issueId = $payload[$issueKey]['number'];
        $issueAuthorId = $payload[$issueKey]['user']['id'];
        $commentAuthorId = $payload['comment']['user']['id'];

        if ($commentAuthorId === $issueAuthorId) {
            foreach ($this->githubClient->issues()->labels()->all($repoUser, $repoName, $issueId) as $label) {
                if ('pending author' === $label['name']) {
                    $this->githubClient->issues()->labels()->remove($repoUser, $repoName, $issueId, 'pending author');
                    break;
                }
            }
        }
    }
}
