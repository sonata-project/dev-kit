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

namespace App\Github\Api;

use App\Domain\Value\Repository;
use Github\Client as GithubClient;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Topics
{
    private GithubClient $github;

    public function __construct(GithubClient $github)
    {
        $this->github = $github;
    }

    /**
     * @return string[]
     */
    public function get(Repository $repository): array
    {
        $topics = $this->github->repo()->topics(
            $repository->vendor(),
            $repository->name()
        );
        Assert::keyExists($topics, 'names');

        $topics = $topics['names'];

        sort($topics);

        return $topics;
    }

    public function replace(Repository $repository, array $topics): void
    {
        $this->github->repo()->replaceTopics(
            $repository->vendor(),
            $repository->name(),
            $topics
        );
    }
}
