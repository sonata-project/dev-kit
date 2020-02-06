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
        $eventName = $request->headers->get('X-GitHub-Event');
        $payload = json_decode($request->getContent(), true);

        if ('' === $devKitToken || $request->query->get('token') !== $devKitToken) {
            return new JsonResponse(['message' => 'Invalid credentials'], 403);
        }

        switch ($eventName) {
            case 'ping':
                return new Response();
            case 'issue_comment':
                $githubHookProcessor->processPendingAuthor($eventName, $payload);

                return new Response();
            case 'pull_request':
                $githubHookProcessor->processReviewLabels($eventName, $payload);
                $githubHookProcessor->processPendingAuthor($eventName, $payload);

                return new Response();
            case 'pull_request_review_comment':
                $githubHookProcessor->processPendingAuthor($eventName, $payload);

                return new Response();
            default:
                return new JsonResponse(['message' => 'Nothing to do for: '.$eventName], 200);
        }
    }
}
