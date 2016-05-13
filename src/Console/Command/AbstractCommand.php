<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DevKit\Console\Command;

use Sonata\DevKit\Config\DevKitConfiguration;
use Sonata\DevKit\Config\ProjectsConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    const GITHUB_GROUP = 'sonata-project';
    const GITHUB_USER = 'SonataCI';
    const GITHUB_EMAIL = 'thomas+ci@sonata-project.org';
    const PACKAGIST_GROUP = 'sonata-project';

    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var array
     */
    protected $configs;

    /**
     * @var string|null
     */
    protected $githubAuthKey = null;

    /**
     * @var \Packagist\Api\Client
     */
    protected $packagistClient;

    /**
     * @var \Github\Client
     */
    protected $githubClient = false;

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);

        $processor = new Processor();
        $devKitConfigs = $processor->processConfiguration(new DevKitConfiguration(), array(
            'sonata' => Yaml::parse(file_get_contents(__DIR__.'/../../../config/dev-kit.yml')),
        ));
        $projectsConfigs = $processor->processConfiguration(new ProjectsConfiguration($devKitConfigs), array(
            'sonata' => array('projects' => Yaml::parse(file_get_contents(__DIR__.'/../../../config/projects.yml'))),
        ));
        $this->configs = array_merge($devKitConfigs, $projectsConfigs);

        if (getenv('GITHUB_OAUTH_TOKEN')) {
            $this->githubAuthKey = getenv('GITHUB_OAUTH_TOKEN');
        }

        $this->packagistClient = new \Packagist\Api\Client();

        $this->githubClient = new \Github\Client();
        if ($this->githubAuthKey) {
            $this->githubClient->authenticate($this->githubAuthKey, null, \Github\Client::AUTH_HTTP_TOKEN);
        }
    }
}
