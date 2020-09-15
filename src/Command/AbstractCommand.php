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

namespace App\Command;

use App\Config\DevKitConfiguration;
use App\Config\ProjectsConfiguration;
use Packagist\Api\Result\Package;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Symfony\Component\String\u;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    public const GITHUB_GROUP = 'sonata-project';
    public const GITHUB_USER = 'SonataCI';
    public const GITHUB_EMAIL = 'thomas+ci@sonata-project.org';
    public const PACKAGIST_GROUP = 'sonata-project';
    public const BOT_NAME = 'SonataCI';

    /**
     * @var array
     */
    protected $configs;

    /**
     * @var string|null
     */
    protected $githubOauthToken = null;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $processor = new Processor();
        $devKitConfigs = $processor->processConfiguration(new DevKitConfiguration(), [
            'sonata' => Yaml::parse(file_get_contents(__DIR__.'/../../config/dev-kit.yaml')),
        ]);
        $projectsConfigs = $processor->processConfiguration(new ProjectsConfiguration($devKitConfigs), [
            'sonata' => ['projects' => Yaml::parse(file_get_contents(__DIR__.'/../../config/projects.yaml'))],
        ]);
        $this->configs = array_merge($devKitConfigs, $projectsConfigs);

        if (getenv('GITHUB_OAUTH_TOKEN')) {
            $this->githubOauthToken = getenv('GITHUB_OAUTH_TOKEN');
        }
    }

    /**
     * Returns repository name without vendor prefix.
     */
    final protected function getRepositoryName(Package $package): string
    {
        $repositoryArray = u($package->getRepository())->split('/');

        return str_replace('.git', '', end($repositoryArray));
    }
}
