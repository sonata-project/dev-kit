<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../autoload.php';

use Silex\Provider\TwigServiceProvider;
use Sonata\DevKit\GithubHookProcessor;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$githubHookProcessor = new GithubHookProcessor(getenv('GITHUB_OAUTH_TOKEN') ? getenv('GITHUB_OAUTH_TOKEN') : null);
$devKitToken = getenv('DEK_KIT_TOKEN');

$app = new Silex\Application();

$app
    ->register(new TwigServiceProvider(), [
        'twig.path' => __DIR__.'/../views',
    ]);

$app->get('/', function () use ($app) {
    $revision = file_exists(__DIR__.'/../REVISION') ? trim(file_get_contents(__DIR__.'/../REVISION')) : null;

    return $app['twig']->render('index.html.twig', [
        'revision' => $revision,
    ]);
});

$app->post('/github', function (Request $request) use ($app, $githubHookProcessor, $devKitToken) {
    $eventName = $request->headers->get('X-GitHub-Event');
    $payload = json_decode($request->getContent(), true);

    if (!$devKitToken || $request->query->get('token') !== $devKitToken) {
        return $app->json(['message' => 'Invalid credentials'], 403);
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
});

$app->run();
