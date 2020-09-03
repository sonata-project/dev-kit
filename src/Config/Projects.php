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

namespace App\Config;

use App\Domain\Value\Project;
use Packagist\Api\Client;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Projects
{
    private Client $packagist;

    /**
     * @var array<string, Project>
     */
    private array $projects;

    public function __construct(Client $packagist)
    {
        $this->packagist = $packagist;

        $processor = new Processor();
        $devKitConfigs = $processor->processConfiguration(new DevKitConfiguration(), [
            'sonata' => Yaml::parse(file_get_contents(__DIR__.'/../../config/dev-kit.yml')),
        ]);
        $projectsConfigs = $processor->processConfiguration(new ProjectsConfiguration($devKitConfigs), [
            'sonata' => ['projects' => Yaml::parse(file_get_contents(__DIR__.'/../../config/projects.yml'))],
        ]);
        $this->configs = array_merge($devKitConfigs, $projectsConfigs);
    }
}
