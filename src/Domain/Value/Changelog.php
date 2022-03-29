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
use App\Github\Domain\Value\Release\Tag;
use Packagist\Api\Result\Package;
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

    /**
     * @param Section[] $sections
     */
    private function __construct(string $headline, array $sections)
    {
        $this->headline = TrimmedNonEmptyString::fromString($headline)->toString();

        Assert::notEmpty($sections);
        $this->sections = $sections;
    }

    /**
     * @param Section[] $sections
     */
    public static function fromValues(string $headline, array $sections): self
    {
        return new self($headline, $sections);
    }

    /**
     * @param PullRequest[] $pullRequests
     */
    public static function fromPullRequests(array $pullRequests, Tag $next, Tag $current, Package $package): self
    {
        $headline = sprintf(
            '## [%s](%s/compare/%s...%s) - %s',
            $next->toString(),
            $package->getRepository(),
            $current->toString(),
            $next->toString(),
            date('Y-m-d')
        );

        $changelogs = array_map(
            static fn (PullRequest $pr): array => $pr->changelog(),
            $pullRequests
        );

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
        $markdown = [$this->headline];
        foreach ($this->sections as $section) {
            $markdown[] = $section->asMarkdown();
            $markdown[] = '';
        }

        return implode(\PHP_EOL, $markdown);
    }
}
