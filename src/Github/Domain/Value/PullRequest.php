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
final class PullRequest
{
    private int $number;
    private string $title;
    private \DateTime $updatedAt;
    private User $user;

    private function __construct(int $number, string $title, string $updatedAt, User $user)
    {
        Assert::stringNotEmpty($title);
        Assert::greaterThan($number, 0);

        $this->number = $number;
        $this->title = $title;
        $this->updatedAt = new \DateTime(
            $updatedAt,
            new \DateTimeZone('UTC')
        );
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

        Assert::keyExists($config, 'user');
        Assert::stringNotEmpty($config['user']);

        return new self(
            $config['number'],
            $config['title'],
            $config['updated_at'],
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

    public function user(): User
    {
        return $this->user;
    }

    public function toString(): string
    {
        return sprintf(
            '#%d > %s - %s',
            $this->number,
            $pullRequest['base']['ref'],
            $this->title
        );
    }
}
