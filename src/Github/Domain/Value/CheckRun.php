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

use App\Domain\Value\TrimmedNonEmptyString;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class CheckRun
{
    private const CONCLUSION_ACTION_REQUIRED = 'action_required';
    private const CONCLUSION_CANCELLED = 'cancelled';
    private const CONCLUSION_FAILURE = 'failure';
    private const CONCLUSION_NEUTRAL = 'neutral';
    private const CONCLUSION_SKIPPED = 'skipped';
    private const CONCLUSION_STALE = 'stale';
    private const CONCLUSION_SUCCESS = 'success';
    private const CONCLUSION_TIMED_OUT = 'timed_out';

    private const STATUS_COMPLETED = 'completed';
    private const STATUS_IN_PROGRESS = 'in_progress';
    private const STATUS_QUEUED = 'queued';

    private string $status;
    private ?string $conclusion;
    private string $name;
    private string $detailsUrl;

    private function __construct(string $status, ?string $conclusion, string $name, string $detailsUrl)
    {
        $this->status = $status;
        $this->conclusion = $conclusion;
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
        $this->detailsUrl = TrimmedNonEmptyString::fromString($detailsUrl)->toString();
    }

    /**
     * @param mixed[] $response
     */
    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'status');
        Assert::stringNotEmpty($response['status']);
        Assert::oneOf(
            $response['status'],
            [
                self::STATUS_COMPLETED,
                self::STATUS_IN_PROGRESS,
                self::STATUS_QUEUED,
            ]
        );

        Assert::keyExists($response, 'conclusion');

        if (null !== $response['conclusion']) {
            Assert::stringNotEmpty($response['conclusion']);
            Assert::oneOf(
                $response['conclusion'],
                [
                    self::CONCLUSION_ACTION_REQUIRED,
                    self::CONCLUSION_CANCELLED,
                    self::CONCLUSION_FAILURE,
                    self::CONCLUSION_NEUTRAL,
                    self::CONCLUSION_SKIPPED,
                    self::CONCLUSION_STALE,
                    self::CONCLUSION_SUCCESS,
                    self::CONCLUSION_TIMED_OUT,
                ]
            );
        }

        Assert::keyExists($response, 'name');
        Assert::stringNotEmpty($response['name']);

        Assert::keyExists($response, 'details_url');
        Assert::stringNotEmpty($response['details_url']);

        return new self(
            $response['status'],
            $response['conclusion'],
            $response['name'],
            $response['details_url']
        );
    }

    public function isSuccessful(): bool
    {
        if (null === $this->conclusion) {
            return false;
        }

        return self::CONCLUSION_SUCCESS === $this->conclusion;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function conclusion(): ?string
    {
        return $this->conclusion;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function nameFormatted(): string
    {
        if (self::CONCLUSION_SUCCESS === $this->conclusion) {
            return sprintf(
                '<info>%s</info>',
                $this->name
            );
        }

        if (self::CONCLUSION_NEUTRAL === $this->conclusion) {
            return sprintf(
                '<comment>%s</comment>',
                $this->name
            );
        }

        return sprintf(
            '<error>%s</error>',
            $this->name
        );
    }

    public function detailsUrl(): string
    {
        return $this->detailsUrl;
    }
}
