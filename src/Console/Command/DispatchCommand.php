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

use Github\Exception\ExceptionInterface;
use Packagist\Api\Result\Package;
use SebastianBergmann\Diff\Differ;
use Sonata\DevKit\Config\Configuration;
use Sonata\DevKit\Console\Style\SonataStyle;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class DispatchCommand extends Command
{
    /**
     * @var bool
     */
    private $apply;

    /**
     * @var SonataStyle
     */
    private $io;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var \Packagist\Api\Client
     */
    private $packagistClient;

    /**
     * @var \Github\Client
     */
    private $githubClient = false;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('dispatch')
            ->setDescription('Dispatches configuration and documentation files for all sonata projects.')
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applies differences across repositories')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SonataStyle($input, $output);
        $this->apply = $input->getOption('apply');

        $configs = Yaml::parse(file_get_contents(__DIR__.'/../../../.sonata-project.yml'));
        $processor = new Processor();
        $this->configs = $processor->processConfiguration(new Configuration(), array('sonata' => $configs));

        $this->packagistClient = new \Packagist\Api\Client();

        $this->githubClient = new \Github\Client();
        if (getenv('GITHUB_OAUTH_TOKEN')) {
            $this->githubClient->authenticate(getenv('GITHUB_OAUTH_TOKEN'), null, \Github\Client::AUTH_HTTP_TOKEN);
        }

        $this->differ = new Differ();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->apply) {
            $this->io->warning('This is a dry run execution. No change will be applied here.');
        }

        foreach ($this->configs['projects'] as $name => $projectConfig) {
            try {
                $package = $this->packagistClient->get('sonata-project/'.$name);
                $this->io->title($package->getName());
                $repositoryName = $this->getRepositoryName($package);
                $this->updateLabels($repositoryName);
                $this->dispatchFiles($repositoryName);
            } catch (ExceptionInterface $e) {
                $this->io->error('Failed with message: '.$e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Returns repository name without vendor prefix.
     *
     * @param Package $package
     *
     * @return string
     */
    private function getRepositoryName(Package $package)
    {
        $repositoryArray = explode('/', $package->getRepository());

        return str_replace('.git', '', end($repositoryArray));
    }

    /**
     * @param string $repositoryName
     */
    private function updateLabels($repositoryName)
    {
        $this->io->section('Labels');

        $configuredLabels = $this->configs['labels'];
        $missingLabels = $configuredLabels;

        $headers = array('Name', 'Actual color', 'Needed Color', 'State');
        $rows = array();

        foreach ($this->githubClient->repo()->labels()->all('sonata-project', $repositoryName) as $label) {
            $name = $label['name'];
            $color = $label['color'];

            $shouldExist = array_key_exists($name, $configuredLabels);
            $configuredColor = $shouldExist ? $configuredLabels[$name]['color'] : null;
            $shouldBeUpdated = $shouldExist && $color !== $configuredColor;

            if ($shouldExist) {
                unset($missingLabels[$name]);
            }

            $state = null;
            if (!$shouldExist) {
                $state = 'Deleted';
                if ($this->apply) {
                    $this->githubClient->repo()->labels()->remove('sonata-project', $repositoryName, $name);
                }
            } elseif ($shouldBeUpdated) {
                $state = 'Updated';
                if ($this->apply) {
                    $this->githubClient->repo()->labels()->update('sonata-project', $repositoryName, $name, array(
                        'name'  => $name,
                        'color' => $configuredColor,
                    ));
                }
            }

            if ($state) {
                array_push($rows, array(
                    $name,
                    '#'.$color,
                    $configuredColor ? '#'.$configuredColor : 'N/A',
                    $state,
                ));
            }
        }

        foreach ($missingLabels as $name => $label) {
            $color = $label['color'];

            if ($this->apply) {
                $this->githubClient->repo()->labels()->create('sonata-project', $repositoryName, array(
                    'name'  => $name,
                    'color' => $color,
                ));
            }
            array_push($rows, array($name, 'N/A', '#'.$color, 'Created'));
        }

        usort($rows, function ($row1, $row2) {
            return strcasecmp($row1[0], $row2[0]);
        });

        if (empty($rows)) {
            $this->io->comment('Nothing to be changed.');
        } else {
            $this->io->table($headers, $rows);

            if ($this->apply) {
                $this->io->success('Labels successfully updated.');
            }
        }
    }

    /**
     * @param $repositoryName
     */
    private function dispatchFiles($repositoryName)
    {
        $this->io->section('Documentation');

        foreach ($this->configs['files'] as $fileBlock) {
            list($localPath, $distPath) = explode(':', $fileBlock);

            $this->io->comment($distPath);
            $localContent = file_get_contents(__DIR__.'/../../../'.$localPath);
            $distContent = '';
            if ($this->githubClient->repo()->contents()->exists('sonata-project', $repositoryName, $distPath, 'master')) {
                $file = $this->githubClient->repo()->contents()->show('sonata-project', $repositoryName, $distPath, 'master');
                $distContent = base64_decode($file['content']);
            }

            $this->io->diff($distContent, $localContent);
        }
    }
}
