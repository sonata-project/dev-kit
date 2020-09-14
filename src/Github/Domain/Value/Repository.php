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

use function Symfony\Component\String\u;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Repository
{
    private string $username;
    private string $name;

    private function __construct(string $username, string $name)
    {
        Assert::stringNotEmpty($username);
        Assert::stringNotEmpty($name);

        $this->username = $username;
        $this->name = $name;
    }

    public static function fromString(string $repository): self
    {
        Assert::stringNotEmpty($repository);
        Assert::contains($repository, '/');

        [$username, $name] = u($repository)->split('/');

        return new self(
            $username->toString(),
            $name->toString()
        );
    }

    public function username(): string
    {
        return $this->username;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function toString(): string
    {
        return sprintf(
            '%s/%s',
            $this->username,
            $this->name
        );
    }
}
