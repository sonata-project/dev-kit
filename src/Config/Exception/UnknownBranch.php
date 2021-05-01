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

namespace App\Config\Exception;

use App\Domain\Value\Project;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class UnknownBranch extends \InvalidArgumentException
{
    public static function forName(Project $project, string $name): self
    {
        return new self(sprintf(
            'Could not find branch with name "%s" for project "%s".',
            $name,
            $project->name()
        ));
    }
}
