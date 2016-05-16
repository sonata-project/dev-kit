<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Sonata\DevKit\GithubHookProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$dotenv = new Dotenv\Dotenv(__DIR__.'/..');
$dotenv->load();

$githubHookProcessor = new GithubHookProcessor(getenv('GITHUB_OAUTH_TOKEN') ? getenv('GITHUB_OAUTH_TOKEN') : null);

$app = new Silex\Application();

$app->get('/', function () {
    return new Response("Sonata DevKit\n");
});

$app->post('/github', function (Request $request) use ($githubHookProcessor) {
    $eventName = $request->headers->get('X-GitHub-Event');
    $payload = json_decode($request->getContent(), true);

    switch ($eventName) {
        case 'ping':
            return new Response();
        case 'issue_comment':
            $githubHookProcessor->processPendingAuthor($eventName, $payload);

            return new Response();
        case 'pull_request_review_comment':
            $githubHookProcessor->processPendingAuthor($eventName, $payload);

            return new Response();
        default:
            return new JsonResponse(array('message' => 'Not Implemented: '.$eventName), 501);
    }
});

$app->run();
