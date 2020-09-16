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
use Packagist\Api\Client as PackagistClient;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Projects
{
    public const PACKAGIST_GROUP = 'sonata-project';

    private PackagistClient $packagist;

    /**
     * @var array<string, Project>
     */
    private array $projects = [];

    public function __construct(PackagistClient $packagist)
    {
        $this->packagist = $packagist;
        $this->logger = $logger;

        $processor = new Processor();
        $projectsConfigs = $processor->processConfiguration(new ProjectsConfiguration(), [
            'sonata' => ['projects' => Yaml::parse(file_get_contents(__DIR__.'/../../config/projects.yaml'))],
        ]);

        foreach ($projectsConfigs['projects'] as $name => $config) {
            $packageName = static::PACKAGIST_GROUP.'/'.$name;

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
        Assert::keyExists(
            $this->projects,
            $name,
            sprintf(
                'Unknown project: %s',
                $name
            )
        );

        return $this->projects[$name];
    }

    /**
     * @param string[] $names
     *
     * @return array<string, Project>
     */
    public function byNames(array $names): array
    {
        Assert::notEmpty($names);
        Assert::allStringNotEmpty($names);

        $projects = [];
        foreach ($names as $name) {
            Assert::keyExists($this->projects, $name, sprintf('Unknown project: %s', $name));

            $projects[$name] = $this->projects[$name];
        }

        return $projects;
    }
}
