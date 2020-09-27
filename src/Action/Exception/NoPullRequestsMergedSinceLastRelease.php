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

namespace App\Action\Exception;

use App\Domain\Value\Project;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class NoPullRequestsMergedSinceLastRelease extends \RuntimeException
{
    public static function forProject(Project $project, \DateTimeImmutable $lastRelease, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'No pull requests merged since last release "%s" for Project "%s".',
                $lastRelease->format('Y-m-d H:i:s'),
                $project->name()
            ),
            0,
            $previous
        );
    }
}
