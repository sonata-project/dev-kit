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

namespace App\Domain\Exception;

use App\Domain\Value\Project;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class NoBranchesAvailable extends \InvalidArgumentException
{
    public static function forProject(Project $project, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Project "%s" has no branches configured.',
                $project->name()
            ),
            0,
            $previous
        );
    }
}
