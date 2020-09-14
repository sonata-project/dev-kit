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

namespace App\Github\Domain\Value\Webhook;

use App\Github\Domain\Value\Repository;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Action
{
    private string $value;

    private function __construct(string $value)
    {
        $value = trim($value);
        Assert::stringNotEmpty($value);

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function SYNCHRONIZE(): self
    {
        return self::fromString('synchronize');
    }

    public static function CREATED(): self
    {
        return self::fromString('created');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->toString();
    }

    /**
     * @param Action[] $others
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
