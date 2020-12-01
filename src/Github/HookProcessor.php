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

use App\Domain\Value\Repository;
use App\Github\Api\Comments;
use App\Github\Api\Issues;
use App\Github\Domain\Value\Comment;
use App\Github\Domain\Value\IncomingWebhook\Action;
use App\Github\Domain\Value\IncomingWebhook\Event;
use App\Github\Domain\Value\IncomingWebhook\Payload;
use App\Github\Domain\Value\Label;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class HookProcessor
{
    private Issues $issues;
    private Comments $comments;
    private NotifierInterface $notifier;
    private LoggerInterface $logger;

    public function __construct(Issues $issues, Comments $comments, NotifierInterface $notifier, LoggerInterface $logger)
    {
        $this->issues = $issues;
        $this->comments = $comments;
        $this->notifier = $notifier;
        $this->logger = $logger;
    }

    /**
     * Removes the "Pending Author" label if the author respond on an issue or pull request.
     *
     * Github events: issue_comment, pull_request_review_comment
     */
    public function processPendingAuthorLabel(Payload $payload): void
    {
        if (!$payload->action()->equalsOneOf([
            Action::CREATED(),
            Action::SYNCHRONIZE(),
        ])) {
            return;
        }

        if (!$payload->isTheCommentFromTheAuthor()) {
            return;
        }

        $this->issues->removeLabel(
            Repository::fromIncomingWebhookPayload($payload),
            $payload->issue(),
            Label::PendingAuthor()
        );
    }

    /**
     * Manages RTM label.
     *
     * - If a PR is updated and 'RTM' is set, it is removed.
     */
    public function processReviewLabel(Payload $payload): void
    {
        if (!$payload->action()->equals(Action::SYNCHRONIZE())) {
            return;
        }

        $this->issues->removeLabel(
            Repository::fromIncomingWebhookPayload($payload),
            $payload->issue(),
            Label::RTM()
        );
    }

    /**
     * Performs an action and reports back via comment:.
     *
     * 1) "/request-release" - requests a release of a bundle in Slack's #releases channel
     */
    public function magicAction(Payload $payload): void
    {
        $this->logger->debug(
            'Handle incoming webhook',
            [
                'method' => __METHOD__,
            ]
        );

        $magicActions = [
            '/request-release' => 'request-release.markdown',
        ];

        if (!$payload->action()->equals(Action::CREATED())) {
            $this->logger->debug('Action is not "created", skipping!');

            return;
        }

        if (!$payload->event()->equalsOneOf([Event::ISSUE(), Event::ISSUE_COMMENT()])) {
            $this->logger->debug('Event is not "issue" or "issue_comment", skipping!');

            return;
        }

        if (!$payload->hasComment()) {
            return;
        }

        $comment = $payload->comment();
        Assert::isInstanceOf($comment, Comment::class);

        foreach ($magicActions as $body => $filename) {
            if ($body !== $comment->body()) {
                $this->logger->debug(sprintf(
                    'Comment is not "%s", skipping!',
                    $body
                ));

                continue;
            }

            $repository = Repository::fromIncomingWebhookPayload($payload);

            $filepath = sprintf(
                '%s/../../templates/github/%s',
                __DIR__,
                $filename
            );

            if (!file_exists($filepath)) {
                $this->logger->debug(sprintf(
                    'Could not find file "%s" for "%s", skipping!',
                    $filepath,
                    $body
                ));

                continue;
            }

            $fileContent = file_get_contents($filepath);
            Assert::string($fileContent);

            $contents = u($fileContent)
                ->replace('#handle#', $comment->author()->handle())
                ->replace('#project#', $payload->repository()->name())
                ->toString();

            $this->comments->create(
                $repository,
                $payload->issue(),
                $contents
            );

            $this->comments->remove(
                $repository,
                $comment
            );

            if ('/request-release' === $body) {
                $message = sprintf(
                    '%s requested a release for %s in %s',
                    $comment->author()->handle(),
                    $payload->repository()->name(),
                    $payload->htmlUrl()->toString()
                );

                $notification = new Notification(
                    $message,
                    [
                        'chat/slack',
                    ]
                );

                $this->notifier->send($notification, new Recipient());
            }
        }
    }

    /**
     * Adds a comment when a comment with a dedicated content ist created + it removes the comment with the dedicated content:.
     *
     * 1) "/help" - shows all supported commands
     *
     * 2) "/bundles-todo-list" - adds a todo-list with all bundles
     */
    public function magicComment(Payload $payload): void
    {
        $this->logger->debug(
            'Handle incoming webhook',
            [
                'method' => __METHOD__,
            ]
        );

        $magicComments = [
            '/help' => 'help.markdown',
            '/bundles-todo-list' => 'bundles-todo-list.markdown',
        ];

        if (!$payload->action()->equals(Action::CREATED())) {
            $this->logger->debug('Action is not "created", skipping!');

            return;
        }

        if (!$payload->event()->equalsOneOf([Event::ISSUE(), Event::ISSUE_COMMENT()])) {
            $this->logger->debug('Event is not "issue" or "issue_comment", skipping!');

            return;
        }

        if (!$payload->hasComment()) {
            return;
        }

        $comment = $payload->comment();
        Assert::isInstanceOf($comment, Comment::class);

        foreach ($magicComments as $body => $filename) {
            if ($body !== $comment->body()) {
                $this->logger->debug(sprintf(
                    'Comment is not "%s", skipping!',
                    $body
                ));

                continue;
            }

            $repository = Repository::fromIncomingWebhookPayload($payload);

            $filepath = sprintf(
                '%s/../../templates/github/%s',
                __DIR__,
                $filename
            );

            if (!file_exists($filepath)) {
                $this->logger->debug(sprintf(
                    'Could not find file "%s" for "%s", skipping!',
                    $filepath,
                    $body
                ));

                continue;
            }

            $fileContent = file_get_contents($filepath);
            Assert::string($fileContent);

            $contents = u($fileContent)
                ->replace('#handle#', $comment->author()->handle())
                ->toString();

            $this->comments->create(
                $repository,
                $payload->issue(),
                $contents
            );

            $this->comments->remove(
                $repository,
                $comment
            );
        }
    }
}
