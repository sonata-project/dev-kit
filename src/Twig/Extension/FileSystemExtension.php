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

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class FileSystemExtension extends AbstractExtension
{
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_exists', [$this, 'fileExists']),
            new TwigFunction('has_dependency', [$this, 'hasDependency']),
        ];
    }

    public function fileExists(string $fileName): bool
    {
        return $this->filesystem->exists($fileName);
    }

    public function hasDependency(string $projectDir, string $dependency): bool
    {
        $composerPath = $projectDir.'/composer.json';
        $composerContent = file_get_contents($composerPath);
        if (false === $composerContent) {
            throw new IOException(sprintf('Cannot read composer.json at path "%s"', $composerPath));
        }

        return str_contains($composerContent, $dependency);
    }
}
