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
 * @author Jordi Sala <jordism91@gmail.com>
 */
final class PhpunitExtension
{
    private string $extension;

    private function __construct(string $extension)
    {
        $this->extension = TrimmedNonEmptyString::fromString($extension)->toString();
    }

    public static function fromString(string $extension): self
    {
        return new self($extension);
    }

    public function extension(): string
    {
        return $this->extension;
    }
}
