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
final class Branch
{
    private string $name;
    private Commit $commit;

    private function __construct(string $name, Commit $commit)
    {
        $this->name = TrimmedNonEmptyString::fromString($name)->toString();
        $this->commit = $commit;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        dd($response);

        Assert::keyExists($response, 'name');
        Assert::stringNotEmpty($response['name']);

        Assert::keyExists($response, 'commit');
        Assert::notEmpty($response['commit']);

        return new self(
            $response['name'],
            Commit::fromResponse($response['commit'])
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function commit(): Commit
    {
        return $this->commit;
    }
}
