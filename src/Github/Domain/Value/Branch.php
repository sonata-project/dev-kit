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
final class Branch
{
    private Commit $commit;

    private function __construct(Commit $commit)
    {
        $this->commit = $commit;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'commit');
        Assert::notEmpty($response['commit']);

        return new self(
            Commit::fromResponse($response['commit'])
        );
    }

    public function commit(): Commit
    {
        return $this->commit;
    }
}
