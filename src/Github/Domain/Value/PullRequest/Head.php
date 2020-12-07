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

namespace App\Github\Domain\Value\PullRequest;

use App\Domain\Value\TrimmedNonEmptyString;
use App\Github\Domain\Value\PullRequest\Head\Repo;
use App\Github\Domain\Value\Sha;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Head
{
    private string $ref;
    private Sha $sha;
    private ?Repo $repo;

    private function __construct(string $ref, Sha $sha, ?Repo $repo)
    {
        $this->ref = TrimmedNonEmptyString::fromString($ref)->toString();
        $this->sha = $sha;
        $this->repo = $repo;
    }

    public static function fromResponse(array $config): self
    {
        Assert::notEmpty($config);

        Assert::keyExists($config, 'ref');
        Assert::stringNotEmpty($config['ref']);

        Assert::keyExists($config, 'sha');
        Assert::stringNotEmpty($config['sha']);

        Assert::keyExists($config, 'repo');
        if (\is_array($config['repo'])) {
            Assert::notEmpty($config['repo']);
        }

        if (null === $config['repo']) {
            $repo = null;
        } else {
            $repo = Repo::fromResponse($config['repo']);
        }

        return new self(
            $config['ref'],
            Sha::fromString($config['sha']),
            $repo
        );
    }

    public function ref(): string
    {
        return $this->ref;
    }

    public function sha(): Sha
    {
        return $this->sha;
    }

    public function repo(): ?Repo
    {
        return $this->repo;
    }
}
