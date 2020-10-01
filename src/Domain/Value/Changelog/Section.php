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

namespace App\Domain\Value\Changelog;

use App\Domain\Value\TrimmedNonEmptyString;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Section
{
    private string $headline;

    /**
     * @var string[]
     */
    private array $lines;

    private function __construct(string $headline, array $lines)
    {
        $headline = TrimmedNonEmptyString::fromString($headline)->toString();

        Assert::oneOf(
            $headline,
            [
                'Added',
                'Changed',
                'Deprecated',
                'Fixed',
                'Removed',
            ]
        );

        $this->headline = $headline;

        Assert::notEmpty($lines);
        $this->lines = $lines;
    }

    public static function fromValues(string $headline, array $lines): self
    {
        return new self($headline, $lines);
    }

    public function headline(): string
    {
        return $this->headline;
    }

    /**
     * @return string[]
     */
    public function lines(): array
    {
        return $this->lines;
    }

    public function asMarkdown(): string
    {
        return implode(PHP_EOL, array_merge(
            [
                sprintf(
                    '### %s',
                    $this->headline
                ),
            ],
            $this->lines
        ));
    }
}
