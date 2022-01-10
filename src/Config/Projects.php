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

use App\Config\Exception\UnknownProject;
use App\Domain\Value\Project;
use Packagist\Api\Client as PackagistClient;
use Packagist\Api\Result\Package;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Projects
{
    private PackagistClient $packagist;

    /**
     * @var array<string, Project>
     */
    private array $projects = [];

    public function __construct(PackagistClient $packagist)
    {
        $this->packagist = $packagist;

        $processor = new Processor();
        $projectsConfigs = $processor->processConfiguration(new ProjectsConfiguration(), [
            'sonata' => ['projects' => Yaml::parseFile(__DIR__.'/../../config/projects.yaml')],
        ]);

        foreach ($projectsConfigs['projects'] as $name => $config) {
            $package = $this->packagist->get(sprintf(
                'sonata-project/%s',
                $name
            ));
            Assert::isInstanceOf($package, Package::class);

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
        try {
            Assert::stringNotEmpty($name);
            Assert::keyExists($this->projects, $name);
        } catch (\InvalidArgumentException $e) {
            throw UnknownProject::forName($name);
        }

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
            try {
                Assert::keyExists($this->projects, $name);
            } catch (\InvalidArgumentException $e) {
                throw UnknownProject::forName($name);
            }

            $projects[$name] = $this->projects[$name];
        }

        return $projects;
    }
}
