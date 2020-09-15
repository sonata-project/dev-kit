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

namespace App\Util;

use Packagist\Api\Result\Package;
use function Symfony\Component\String\u;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Util
{
    public static function getRepositoryNameWithoutVendorPrefix(Package $package): string
    {
        $repositoryArray = u($package->getRepository())->split('/');

        $lastName = end($repositoryArray);

        if (!$lastName) {
            throw new \LogicException(sprintf(
                'Could not get repository name without vendor prefix for: %s',
                $package->getRepository()
            ));
        }

        return str_replace('.git', '', (string) $lastName);
    }
}
