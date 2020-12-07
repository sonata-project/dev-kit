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

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class ExcludedFile
{
    private string $filename;

    private function __construct(string $filename)
    {
        $this->filename = TrimmedNonEmptyString::fromString($filename)->toString();
    }

    public static function fromString(string $filename): self
    {
        return new self($filename);
    }

    public function filename(): string
    {
        return $this->filename;
    }
}
