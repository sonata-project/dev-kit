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

use App\Github\Domain\Value\PullRequest\Base;
use App\Github\Domain\Value\PullRequest\Head;
use App\Github\Domain\Value\PullRequest\User;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class PullRequest
{
    private int $number;
    private string $title;
    private \DateTime $updatedAt;
    private Base $base;
    private Head $head;
    private User $user;

    private function __construct(int $number, string $title, string $updatedAt, Base $base, Head $head, User $user)
    {
        Assert::stringNotEmpty($title);
        Assert::greaterThan($number, 0);

        $this->number = $number;
        $this->title = $title;
        $this->updatedAt = new \DateTime(
            $updatedAt,
            new \DateTimeZone('UTC')
        );
        $this->base = $base;
        $this->head = $head;
        $this->user = $user;
    }

    public static function fromConfigArray(array $config): self
    {
        Assert::notEmpty($config);

        Assert::keyExists($config, 'number');
        Assert::stringNotEmpty($config['number']);

        Assert::keyExists($config, 'title');
        Assert::stringNotEmpty($config['title']);

        Assert::keyExists($config, 'updated_at');
        Assert::stringNotEmpty($config['updated_at']);

        Assert::keyExists($config, 'base');
        Assert::stringNotEmpty($config['base']);

        Assert::keyExists($config, 'head');
        Assert::stringNotEmpty($config['head']);

        Assert::keyExists($config, 'user');
        Assert::stringNotEmpty($config['user']);

        return new self(
            $config['number'],
            $config['title'],
            $config['updated_at'],
            Base::fromConfigArray($config['base']),
            Head::fromConfigArray($config['head']),
            User::fromConfigArray($config['user'])
        );
    }

    public function number(): int
    {
        return $this->number;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function updatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function base(): Base
    {
        return $this->base;
    }

    public function head(): Head
    {
        return $this->head;
    }

    public function user(): User
    {
        return $this->user;
    }
}
