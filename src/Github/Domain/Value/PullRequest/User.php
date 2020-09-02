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
final class User
{
    private string $login;

    private function __construct(string $login)
    {
        Assert::stringNotEmpty($login);

        $this->login = $login;
    }

    public static function fromConfigArray(array $config): self
    {
        Assert::notEmpty($config);

        Assert::keyExists($config, 'login');
        Assert::stringNotEmpty($config['login']);

        return new self($config['login']);
    }

    public function login(): string
    {
        return $this->login;
    }
}
