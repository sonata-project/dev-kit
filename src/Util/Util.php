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
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Util
{
    public static function getRepositoryNameWithoutVendorPrefix(Package $package): string
    {
        $repository = $package->getRepository();

        Assert::contains(
            $repository,
            '/',
            sprintf(
                'Repository name must contain a slash: %s',
                $repository
            )
        );
        Assert::notEndsWith(
            $repository,
            '/',
            sprintf(
                'Repository name must not end with a slash: %s',
                $repository
            )
        );

        $array = u($repository)->split('/');

        $name = end($array);

        if (!$name) {
            throw new \LogicException(sprintf(
                'Could not get repository name without vendor prefix for: %s',
                $package->getRepository()
            ));
        }

        return u((string) $name)->replace('.git', '')->toString();
    }
}
