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

    use Cache\Adapter\Redis\RedisCachePool;

    $client = new \Redis();
    $client->connect('127.0.0.1', 6379);
#    // Create a PSR6 cache pool
#    $pool = new RedisCachePool($client);
#
#    $client = new \Github\Client();
#    $client->addCache($pool);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
];
