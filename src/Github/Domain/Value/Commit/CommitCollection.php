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

namespace App\Github\Domain\Value\Commit;

use App\Github\Domain\Value\Commit;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class CommitCollection implements \Countable
{
    /** @var Commit[] */
    private array $commits;

    /**
     * @param Commit[] $commits
     */
    private function __construct(array $commits)
    {
        Assert::notEmpty($commits);
        Assert::allIsInstanceOf($commits, Commit::class);

        $this->commits = $commits;
    }

    /**
     * @param Commit[] $commits
     */
    public static function from(array $commits): self
    {
        Assert::notEmpty($commits);
        Assert::allIsInstanceOf($commits, Commit::class);

        return new self($commits);
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return array_map(
            static fn (Commit $commit): string => $commit->message(),
            $this->commits
        );
    }

    public function firstMessage(): string
    {
        return $this->messages()[0];
    }

    public function count(): int
    {
        return \count($this->commits);
    }

    public function uniqueCount(): int
    {
        return \count(array_unique($this->messages()));
    }
}
