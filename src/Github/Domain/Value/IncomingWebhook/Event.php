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

    public static function ISSUE(): self
    {
        return self::fromString('issue');
    }

    public static function ISSUE_COMMENT(): self
    {
        return self::fromString('issue_comment');
    }

    public static function PULL_REQUEST(): self
    {
        return self::fromString('pull_request');
    }

    public static function PULL_REQUEST_REVIEW_COMMENT(): self
    {
        return self::fromString('pull_request_review_comment');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->toString();
    }

    /**
     * @param Event[] $others
     */
    public function equalsOneOf(array $others): bool
    {
        foreach ($others as $other) {
            if ($this->equals($other)) {
                return true;
            }
        }

        return false;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
