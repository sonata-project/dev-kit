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
final class CombinedStatus
{
    private const FAILURE = 'failure';
    private const PENDING = 'pending';
    private const SUCCESS = 'success';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'state');
        Assert::stringNotEmpty($response['state']);
        Assert::oneOf(
            $response['state'],
            [
                self::FAILURE,
                self::PENDING,
                self::SUCCESS,
            ]
        );

        return new self($response['state']);
    }

    public function isSuccessful(): bool
    {
        return self::SUCCESS === $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
