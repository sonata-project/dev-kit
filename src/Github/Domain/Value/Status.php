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

namespace App\Github\Domain\Value;

use Webmozart\Assert\Assert;
use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Status
{
    private string $state;

    private function __construct(string $state)
    {
        Assert::stringNotEmpty($state);

        $this->state = $state;
    }

    public static function fromConfigArray(array $config): self
    {
        Assert::notEmpty($config);

        Assert::keyExists($config, 'state');
        Assert::stringNotEmpty($config['state']);

        return new self($config['state']);
    }

    public function isSuccessful(): bool
    {
        return 'success' === $this->state;
    }

    public function state(): string
    {
        return $this->state;
    }
}
