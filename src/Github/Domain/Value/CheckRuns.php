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
final class CheckRuns
{
    /**
     * @var CheckRun[]
     */
    private array $checkRuns;

    /**
     * @param CheckRun[] $checkRuns
     */
    private function __construct(array $checkRuns)
    {
        $this->checkRuns = $checkRuns;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'check_runs');
        Assert::isArray($response['check_runs']);

        $checkRuns = [];
        foreach ($response['check_runs'] as $checkRun) {
            $checkRuns[] = CheckRun::fromResponse($checkRun);
        }

        return new self(
            $checkRuns
        );
    }

    /**
     * @return CheckRun[]
     */
    public function all(): array
    {
        return $this->checkRuns;
    }
}
