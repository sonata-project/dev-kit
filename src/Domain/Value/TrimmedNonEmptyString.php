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

use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

final class TrimmedNonEmptyString
{
    private string $value;

    private function __construct(string $value, string $message)
    {
        $value = u($value)->trim()->toString();

        Assert::stringNotEmpty($value, $message);

        $this->value = $value;
    }

    public static function fromString(string $value, string $message = ''): self
    {
        return new self($value, $message);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
