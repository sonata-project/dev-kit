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

namespace App\Github\Domain\Value\Hook;

use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Events
{
    /**
     * @var array<string>
     */
    private array $events;

    /**
     * @param array<string> $events
     */
    private function __construct(array $events)
    {
        Assert::notEmpty($events);

        $this->events = $events;
    }

    /**
     * @param array<string, string> $events
     */
    public static function fromArray(array $events): self
    {
        Assert::notEmpty($events);

        return new self($events);
    }

    /**
     * @param array<mixed> $events
     */
    public function equals(array $events): bool
    {
        return 0 === \count(array_diff_assoc($this->events, $events));
    }
}
