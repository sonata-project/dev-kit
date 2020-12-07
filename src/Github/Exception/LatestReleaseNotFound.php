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

namespace App\Github\Exception;

use App\Domain\Value\Repository;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class LatestReleaseNotFound extends \RuntimeException
{
    public static function forRepository(Repository $repository, ?\Throwable $previous = null): self
    {
        return new self(
            sprintf(
                'Could not find latest Release for "%s".',
                $repository->toString()
            ),
            0,
            $previous
        );
    }
}
