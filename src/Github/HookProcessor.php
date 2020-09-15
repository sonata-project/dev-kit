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

use App\Github\Action\RemoveIssueLabel;
use App\Github\Domain\Value\Label;
use App\Github\Domain\Value\Webhook\Action;
use App\Github\Domain\Value\Webhook\Payload;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class HookProcessor
{
    private RemoveIssueLabel $removeIssueLabel;

    public function __construct(RemoveIssueLabel $removeIssueLabel)
    {
        $this->removeIssueLabel = $removeIssueLabel;
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

        $this->removeIssueLabel->__invoke(
            $payload->repository(),
            $payload->issueId(),
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

        $this->removeIssueLabel->__invoke(
            $payload->repository(),
            $payload->issueId(),
            Label::RTM()
        );
    }
}
