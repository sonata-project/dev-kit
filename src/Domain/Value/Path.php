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

use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Path
{
    private string $path;

    private function __construct(string $path)
    {
        $path = TrimmedNonEmptyString::fromString($path)->toString();

        Assert::notStartsWith($path, '/');
        Assert::notEndsWith($path, '/');

        $this->path = $path;
    }

    public static function fromString(string $path): self
    {
        return new self($path);
    }

    public function toString(): string
    {
        return $this->path;
    }
}
