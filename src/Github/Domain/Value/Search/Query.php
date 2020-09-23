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

namespace App\Github\Domain\Value\Search;

use App\Domain\Value\Branch;
use App\Domain\Value\Repository;
use App\Domain\Value\TrimmedNonEmptyString;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Query
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = TrimmedNonEmptyString::fromString($value)->toString();
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function pullRequestsSince(Repository $repository, Branch $branch, \DateTimeImmutable $date, string $excludedAuthor): self
    {
        return new self(
            sprintf(
                'repo:%s type:pr is:merged base:%s merged:>%s -author:%s',
                $repository->toString(),
                $branch->name(),
                $date->format('Y-m-d\TH:i:s\Z'), // @todo check if there is a better way to format the datetime like this
                $excludedAuthor
            )
        );
    }

    public function toString(): string
    {
        return $this->value;
    }
}
