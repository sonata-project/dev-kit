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

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GithubExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_dev_branch', [$this, 'isDevBranch']),
            new TwigFunction('is_dev_master', [$this, 'isDevMaster']),
        ];
    }

    public function isDevBranch(string $version): bool
    {
        return 0 === strpos($version, 'dev-');
    }

    public function isDevMaster(string $version): bool
    {
        return 'dev-master' === $version;
    }
}
