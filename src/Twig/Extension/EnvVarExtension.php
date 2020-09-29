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

/**
 * This is used to determine and render the current SymfonyCloud environment.
 *
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
class EnvVarExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'env',
                [$this, 'getEnv']
            ),
            new TwigFunction(
                'has_env',
                [$this, 'hasEnv']
            ),
        ];
    }

    /**
     * @return string|false
     */
    public function getEnv(string $env)
    {
        if (!$this->hasEnv($env)) {
            return '';
        }

        return getenv($env);
    }

    public function hasEnv(string $env): bool
    {
        if (getenv($env)) {
            return true;
        }

        return false;
    }
}
