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

namespace App\Domain\Value;

use App\Domain\Value\Changelog\Section;
use App\Github\Domain\Value\PullRequest;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Changelog
{
    private string $headline;

    /**
     * @var Section[]
     */
    private array $sections;

    private function __construct(string $headline, array $sections)
    {
        $this->headline = TrimmedNonEmptyString::fromString($headline)->toString();

        Assert::notEmpty($sections);
        $this->sections = $sections;
    }

    public static function fromValues(string $headline, array $sections): self
    {
        return new self($headline, $sections);
    }

    public static function fromPullRequests(string $headline, array $pullRequests): self
    {
        $changelogs = array_map(static function (PullRequest $pr): array {
            return $pr->changelog();
        }, $pullRequests);

        $changelog = array_reduce(
            $changelogs,
            'array_merge_recursive',
            []
        );

        ksort($changelog);

        $sections = [];
        foreach ($changelog as $section => $changes) {
            if (0 === \count($changes)) {
                continue;
            }

            $sections[] = Section::fromValues(
                $section,
                $changes
            );
        }

        return self::fromValues($headline, $sections);
    }

    public function headline(): string
    {
        return $this->headline;
    }

    /**
     * @return Section[]
     */
    public function sections(): array
    {
        return $this->sections;
    }

    public function asMarkdown(): string
    {
        $markdown[] = $this->headline;
        foreach ($this->sections as $section) {
            $markdown[] = '';
            $markdown[] = $section->asMarkdown();
        }

        return implode(PHP_EOL, $markdown);
    }
}
