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

namespace App\Github\Domain\Value\PullRequest;

use Webmozart\Assert\Assert;
use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Base
{
    private string $ref;

    private function __construct(string $ref)
    {
        Assert::stringNotEmpty($ref);

        $this->ref = $ref;
    }

    public static function fromConfigArray(array $config): self
    {
        Assert::notEmpty($config);

        Assert::keyExists($config, 'ref');
        Assert::stringNotEmpty($config['ref']);

        return new self($config['ref']);
    }

    public function ref(): string
    {
        return $this->ref;
    }
}
