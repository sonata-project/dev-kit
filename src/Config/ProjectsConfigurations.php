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
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class ProjectsConfigurations
{
    public const PACKAGIST_GROUP = 'sonata-project';

    private Client $packagist;
    private LoggerInterface $logger;

    /**
     * @var array<string, Project>
     */
    private array $projects = [];

    public function __construct(Client $packagist, LoggerInterface $logger)
    {
        $this->packagist = $packagist;
        $this->logger = $logger;

        $processor = new Processor();
        $devKitConfigs = $processor->processConfiguration(new DevKitConfiguration(), [
            'sonata' => Yaml::parse(file_get_contents(__DIR__.'/../../config/dev-kit.yaml')),
        ]);
        $projectsConfigs = $processor->processConfiguration(new ProjectsConfiguration($devKitConfigs), [
            'sonata' => ['projects' => Yaml::parse(file_get_contents(__DIR__.'/../../config/projects.yaml'))],
        ]);

        $config = array_merge($devKitConfigs, $projectsConfigs);

        foreach ($config['projects'] as $name => $config) {
            $packageName = static::PACKAGIST_GROUP.'/'.$name;

            $this->logger->debug(
                'Call packagist.org to get package information',
                [
                    'package_name' => $packageName
                ]
            );
            $package = $this->packagist->get($packageName);

            $this->projects[$name] = Project::fromValues($name, $config, $package);
        }
    }

    /**
     * @return array<string, Project>
     */
    public function all(): array
    {
        return $this->projects;
    }

    public function byName(string $name): Project
    {
        Assert::stringNotEmpty($name);
        Assert::keyExists($this->projects, $name);

        return $this->projects[$name];
    }

    /**
     * @param array $names
     *
     * @return array<string, Project>
     */
    public function byNames(array $names): array
    {
        Assert::notEmpty($names);
        Assert::allStringNotEmpty($names);

        $projects = [];
        foreach ($names as $name) {
            Assert::keyExists($this->projects, $name);

            $projects[$name] = $this->projects[$name];
        }

        return $projects;
    }
}
