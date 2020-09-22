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
final class Status
{
    private const ERROR = 'error';
    private const PENDING = 'pending';
    private const SUCCESS = 'success';

    private string $state;
    private string $description;
    private string $targetUrl;

    private function __construct(string $state, string $description, string $targetUrl)
    {
        $this->state = $state;
        $this->description = $description;
        $this->targetUrl = $targetUrl;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'state');
        Assert::stringNotEmpty($response['state']);
        Assert::oneOf(
            $response['state'],
            [
                self::ERROR,
                self::PENDING,
                self::SUCCESS,
            ]
        );

        Assert::keyExists($response, 'description');
        Assert::stringNotEmpty($response['description']);

        Assert::keyExists($response, 'target_url');
        Assert::stringNotEmpty($response['target_url']);

        return new self(
            $response['state'],
            $response['description'],
            $response['target_url']
        );
    }

    public function isSuccessful(): bool
    {
        return self::SUCCESS === $this->state;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function targetUrl(): string
    {
        return $this->targetUrl;
    }
}
