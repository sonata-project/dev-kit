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

use App\Domain\Value\Branch;
use App\Domain\Value\NextRelease;
use App\Domain\Value\Repository;
use App\Github\Domain\Value\Release;
use App\Github\Exception\LatestReleaseNotFound;
use Github\Client as GithubClient;
use Github\Exception\RuntimeException;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Releases
{
    public function __construct(private GithubClient $github)
    {
    }

    public function latest(Repository $repository): Release
    {
        try {
            $response = $this->github->repo()->releases()->latest(
                $repository->username(),
                $repository->name()
            );
        } catch (RuntimeException $e) {
            throw LatestReleaseNotFound::forRepository(
                $repository,
                $e
            );
        }

        return Release::fromResponse($response);
    }

    public function latestForBranch(Repository $repository, Branch $branch): Release
    {
        try {
            $response = $this->github->repo()->releases()->all(
                $repository->username(),
                $repository->name()
            );
        } catch (RuntimeException $e) {
            throw LatestReleaseNotFound::forRepositoryAndBranch(
                $repository,
                $branch,
                $e
            );
        }

        foreach ($response as $release) {
            if ($branch->name() === $release['target_commitish']) {
                return Release::fromResponse($release);
            }
        }

        throw LatestReleaseNotFound::forRepositoryAndBranch($repository, $branch);
    }

    public function createDraft(NextRelease $nextRelease): Release
    {
        $repository = $nextRelease->project()->repository();
        $branch = $nextRelease->branch();

        $release = $this->github->repo()->releases()->create(
            $repository->username(),
            $repository->name(),
            [
                'tag_name' => $nextRelease->nextTag()->toString(),
                'target_commitish' => $branch->name(),
                'name' => $nextRelease->nextTag()->toString(),
                'body' => $nextRelease->changelog()->asMarkdown(false),
                'draft' => true,
                'prerelease' => false,
            ]
        );

        return Release::fromDraftResponse($release);
    }
}
