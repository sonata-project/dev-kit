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

use App\Domain\Value\NextRelease;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class CannotGenerateChangelog extends \LogicException
{
    public static function forRelease(NextRelease $nextRelease, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Release "%s" cannot be released yet. Please check labels and changelogs of the pull requests.',
                $nextRelease->nextTag()->toString()
            ),
            0,
            $previous
        );
    }
}
