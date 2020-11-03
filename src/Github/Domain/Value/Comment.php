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

use App\Github\Domain\Value\PullRequest\User;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Comment
{
    private int $id;
    private string $body;
    private \DateTimeImmutable $date;
    private User $user;

    private function __construct(int $id, string $body, \DateTimeImmutable $date, User $user)
    {
        $this->id = $id;
        $this->body = $body;
        $this->date = $date;
        $this->user = $user;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'id');
        Assert::integer($response['id']);
        Assert::greaterThan($response['id'], 0);

        Assert::keyExists($response, 'body');
        Assert::stringNotEmpty($response['body']);

        Assert::keyExists($response, 'created_at');

        return new self(
            $response['id'],
            $response['body'],
            new \DateTimeImmutable($response['created_at']),
            User::fromResponse($response['user'])
        );
    }

    public function id(): int
    {
        return $this->id;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function before(\DateTimeImmutable $date): bool
    {
        return $this->date < $date;
    }
}
