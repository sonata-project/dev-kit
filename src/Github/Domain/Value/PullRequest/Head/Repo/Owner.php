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

namespace App\Github\Domain\Value\PullRequest\Head\Repo;

use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Owner
{
    private string $login;

    private function __construct(string $login)
    {
        Assert::stringNotEmpty($login);

        $this->login = $login;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'login');
        Assert::stringNotEmpty($response['login']);

        return new self($response['login']);
    }

    public function login(): string
    {
        return $this->login;
    }
}
