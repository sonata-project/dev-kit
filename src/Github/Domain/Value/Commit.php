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

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Commit
{
    private string $message;
    private Sha $sha;
    private \DateTimeImmutable $date;

    private function __construct(Sha $sha, string $message, \DateTimeImmutable $date)
    {
        $this->sha = $sha;

        Assert::stringNotEmpty($message);
        $this->message = $message;

        $this->date = $date;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'commit');

        Assert::keyExists($response, 'sha');
        Assert::stringNotEmpty($response['sha']);

        Assert::keyExists($response['commit'], 'message');
        Assert::stringNotEmpty($response['commit']['message']);

        Assert::keyExists($response['commit'], 'committer');
        Assert::keyExists($response['commit']['committer'], 'date');

        return new self(
            Sha::fromString($response['sha']),
            $response['commit']['message'],
            new \DateTimeImmutable($response['commit']['committer']['date'])
        );
    }

    public function message(): string
    {
        return $this->message;
    }

    public function sha(): Sha
    {
        return $this->sha;
    }

    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }
}
