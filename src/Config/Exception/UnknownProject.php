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

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class UnknownProject extends \InvalidArgumentException
{
    public static function forName(string $name): self
    {
        return new self(sprintf(
            'Could not find Project with name "%s".',
            $name
        ));
    }
}
