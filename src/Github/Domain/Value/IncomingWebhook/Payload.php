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

namespace App\Github\Domain\Value\IncomingWebhook;

use App\Github\Domain\Value\Comment;
use App\Github\Domain\Value\Issue;
use App\Github\Domain\Value\Repository;
use App\Github\Domain\Value\Url;
use App\Github\Domain\Value\User;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Payload
{
    private Action $action;
    private Event $event;
    private Issue $issue;
    private Url $htmlUrl;
    private User $issueAuthor;
    private ?Comment $comment;
    private Repository $repository;

    private function __construct(Action $action, Event $event, Issue $issue, Url $htmlUrl, User $issueAuthor, ?Comment $comment, Repository $repository)
    {
        $this->action = $action;
        $this->event = $event;
        $this->issue = $issue;
        $this->htmlUrl = $htmlUrl;
        $this->issueAuthor = $issueAuthor;
        $this->comment = $comment;
        $this->repository = $repository;
    }

    /**
     * @param mixed[] $payload
     */
    public static function fromArray(array $payload, Event $event): self
    {
        Assert::notEmpty($payload);

        Assert::keyExists($payload, 'action');
        $action = Action::fromString($payload['action']);

        $issueKey = $event->equals(Event::ISSUE_COMMENT()) ? 'issue' : 'pull_request';

        Assert::keyExists($payload, $issueKey);
        Assert::keyExists($payload[$issueKey], 'number');
        $issue = Issue::fromInt($payload[$issueKey]['number']);

        Assert::keyExists($payload[$issueKey], 'html_url');
        $htmlUrl = Url::fromString($payload[$issueKey]['html_url']);

        Assert::keyExists($payload[$issueKey], 'user');
        $issueAuthor = User::fromResponse($payload[$issueKey]['user']);

        $comment = null;

        if (\array_key_exists('comment', $payload)) {
            $comment = Comment::fromResponse($payload['comment']);
        }

        Assert::keyExists($payload, 'repository');
        Assert::keyExists($payload['repository'], 'full_name');

        return new self(
            $action,
            $event,
            $issue,
            $htmlUrl,
            $issueAuthor,
            $comment,
            Repository::fromString($payload['repository']['full_name'])
        );
    }

    public static function fromJsonString(string $payload, Event $event): self
    {
        $decodedPayload = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
        Assert::isArray($decodedPayload);

        return self::fromArray(
            $decodedPayload,
            $event
        );
    }

    public function action(): Action
    {
        return $this->action;
    }

    public function event(): Event
    {
        return $this->event;
    }

    public function issue(): Issue
    {
        return $this->issue;
    }

    public function htmlUrl(): Url
    {
        return $this->htmlUrl;
    }

    public function issueAuthor(): User
    {
        return $this->issueAuthor;
    }

    public function hasComment(): bool
    {
        return $this->comment instanceof Comment;
    }

    public function comment(): ?Comment
    {
        return $this->comment;
    }

    public function isTheCommentFromTheAuthor(): bool
    {
        // If it's a PR synchronization, it's obviously done from the author.
        if ($this->action->equals(Action::SYNCHRONIZE())) {
            return true;
        }

        if (null === $this->comment) {
            return false;
        }

        return $this->issueAuthor->id() === $this->comment->author()->id();
    }

    public function repository(): Repository
    {
        return $this->repository;
    }
}
