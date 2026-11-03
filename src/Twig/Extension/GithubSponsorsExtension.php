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

use App\Twig\Runtime\GithubSponsorsRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class GithubSponsorsExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('list_sponsorisable', [GithubSponsorsRuntime::class, 'listSponsorisable']),
        ];
    }
}
