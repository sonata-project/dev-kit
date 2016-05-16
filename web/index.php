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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();

$app->get('/', function () {
    return new Response("Sonata DevKit\n");
});

$app->post('/github', function (Request $request) {
    $eventName = $request->headers->get('X-GitHub-Event');

    switch ($eventName) {
        default:
            return new JsonResponse(array('message' => 'Not Implemented: '.$eventName), 501);
    }
});

$app->run();
