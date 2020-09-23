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

namespace App\Github\Domain\Value\IncomingWebhook;

use App\Domain\Value\TrimmedNonEmptyString;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Event
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

    public function toString(): string
    {
        return $this->value;
    }
}
