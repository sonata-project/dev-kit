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

namespace App\Controller;

use App\Github\Domain\Value\Webhook\Event;
use App\Github\Domain\Value\Payload;
use App\Github\GithubHookProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GithubController
{
    /**
     * @Route("/github", name="github", methods={"POST"})
     */
    public function index(Request $request, GithubHookProcessor $githubHookProcessor, string $devKitToken): Response
    {
        $event = Event::fromString(
            $request->headers->get('X-GitHub-Event')
        );

        if ('' === $devKitToken || $request->query->get('token') !== $devKitToken) {
            return new JsonResponse(
                [
                    'message' => 'Invalid credentials',
                ],
                403
            );
        }

        $payload = Payload::fromJsonString($request->getContent(), $event);

        switch ($event->toString()) {
            case 'ping':
                return new Response();
            case 'issue_comment':
                $githubHookProcessor->processPendingAuthorLabel($payload);

                return new Response();
            case 'pull_request':
                $githubHookProcessor->processReviewLabel($payload);
                $githubHookProcessor->processPendingAuthorLabel($payload);

                return new Response();
            case 'pull_request_review_comment':
                $githubHookProcessor->processPendingAuthorLabel($payload);

                return new Response();
            default:
                return new JsonResponse(
                    [
                        'message' => sprintf(
                            'Nothing to do for: %s',
                            $event->toString()
                        ),
                    ],
                    200
                );
        }
    }
}
