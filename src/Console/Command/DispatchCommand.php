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
use GitWrapper\GitWrapper;
use Packagist\Api\Result\Package;
use Sonata\DevKit\Config\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class DispatchCommand extends Command
{
    const GITHUB_GROUP = 'sonata-project';
    const GITHUB_USER = 'SonataCI';
    const GITHUB_EMAIL = 'ci@sonata-project.org';
    const PACKAGIST_GROUP = 'sonata-project';

    const LABEL_NOTHING_CHANGED = 'Nothing to be changed.';

    /**
     * @var bool
     */
    private $apply;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var array
     */
    private $configs;

    /**
     * @var string|null
     */
    private $githubAuthKey = null;

    /**
     * @var \Packagist\Api\Client
     */
    private $packagistClient;

    /**
     * @var \Github\Client
     */
    private $githubClient = false;

    /**
     * @var GitWrapper
     */
    private $gitWrapper;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var \Twig_Environment
     */
    private $twig;

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
        $this->io = new SymfonyStyle($input, $output);
        $this->apply = $input->getOption('apply');

        $configs = Yaml::parse(file_get_contents(__DIR__.'/../../../.sonata-project.yml'));
        $processor = new Processor();
        $this->configs = $processor->processConfiguration(new Configuration(), array('sonata' => $configs));

        if (getenv('GITHUB_OAUTH_TOKEN')) {
            $this->githubAuthKey = getenv('GITHUB_OAUTH_TOKEN');
        }

        $this->packagistClient = new \Packagist\Api\Client();

        $this->githubClient = new \Github\Client();
        if ($this->githubAuthKey) {
            $this->githubClient->authenticate($this->githubAuthKey, null, \Github\Client::AUTH_HTTP_TOKEN);
        }

        $this->gitWrapper = new GitWrapper();
        $this->fileSystem = new Filesystem();
        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__.'/../../..'));
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
                $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);
                $this->io->title($package->getName());
                $this->updateLabels($package);
                $this->dispatchFiles($package);
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
     * @param Package $package
     */
    private function updateLabels(Package $package)
    {
        $repositoryName = $this->getRepositoryName($package);
        $this->io->section('Labels');

        $configuredLabels = $this->configs['labels'];
        $missingLabels = $configuredLabels;

        $headers = array('Name', 'Actual color', 'Needed Color', 'State');
        $rows = array();

        foreach ($this->githubClient->repo()->labels()->all(static::GITHUB_GROUP, $repositoryName) as $label) {
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
                    $this->githubClient->repo()->labels()->remove(static::GITHUB_GROUP, $repositoryName, $name);
                }
            } elseif ($shouldBeUpdated) {
                $state = 'Updated';
                if ($this->apply) {
                    $this->githubClient->repo()->labels()->update(static::GITHUB_GROUP, $repositoryName, $name, array(
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
                $this->githubClient->repo()->labels()->create(static::GITHUB_GROUP, $repositoryName, array(
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
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        } else {
            $this->io->table($headers, $rows);

            if ($this->apply) {
                $this->io->success('Labels successfully updated.');
            }
        }
    }

    /**
     * @param Package $package
     */
    private function dispatchFiles(Package $package)
    {
        $repositoryName = $this->getRepositoryName($package);
        $projectConfig = $this->configs['projects'][str_replace(static::PACKAGIST_GROUP.'/', '', $package->getName())];

        // No branch to manage, continue to next project.
        if (0 === count($projectConfig['branches'])) {
            return;
        }

        // Clone the repository.
        $clonePath = sys_get_temp_dir().'/sonata-project/'.$repositoryName;
        if ($this->fileSystem->exists($clonePath)) {
            $this->fileSystem->remove($clonePath);
        }
        $git = $this->gitWrapper->cloneRepository(
            'https://'.static::GITHUB_USER.':'.$this->githubAuthKey.'@github.com/'.static::GITHUB_GROUP.'/'.$repositoryName,
            $clonePath
        );
        $git
            ->config('user.name', static::GITHUB_USER)
            ->config('user.email', static::GITHUB_EMAIL)
        ;

        foreach ($projectConfig['branches'] as $branch => $branchConfig) {
            $this->io->section('Files for '.$branch);

            $git->reset(array('hard' => true));

            if (in_array($branch, $git->getBranches()->all(), true)) {
                $git->checkout($branch);
            } else {
                $git->checkout('-b', $branch, '--track', 'origin/'.$branch);
            }

            $this->renderFile($repositoryName, 'project', $clonePath, $projectConfig, $branch);

            $git->add('.', array('all' => true))->getOutput();
            $diff = $git->diff('--color', '--cached')->getOutput();

            if (!empty($diff)) {
                $this->io->writeln($diff);
                if ($this->apply) {
                    $git->commit('DevKit updates')->push();
                }
            } else {
                $this->io->comment(static::LABEL_NOTHING_CHANGED);
            }
        }
    }

    /**
     * @param string $repositoryName
     * @param string $localPath
     * @param string $distPath
     * @param array  $projectConfig
     * @param string $branchName
     */
    private function renderFile($repositoryName, $localPath, $distPath, array $projectConfig, $branchName)
    {
        $localFullPath = __DIR__.'/../../../'.$localPath;
        $localFileType = filetype($localFullPath);
        $distFileType = $this->fileSystem->exists($distPath) ? filetype($distPath) : false;

        if ($localFileType !== $distFileType && false !== $distFileType) {
            throw new \LogicException('File type mismatch between "'.$localPath.'" and "'.$distPath.'"');
        }

        if ('dir' === $localFileType) {
            $localDirectory = dir($localFullPath);
            while (false !== ($entry = $localDirectory->read())) {
                if (!in_array($entry, array('.', '..'), true)) {
                    $this->renderFile(
                        $repositoryName,
                        $localPath.'/'.$entry,
                        $distPath.'/'.$entry,
                        $projectConfig,
                        $branchName
                    );
                }
            }

            return;
        }

        $localContent = file_get_contents($localFullPath);

        if (!$this->fileSystem->exists(dirname($distPath))) {
            $this->fileSystem->mkdir(dirname($distPath));
        }

        $branchConfig = $projectConfig['branches'][$branchName];
        $localPathInfo = pathinfo($localPath);
        if (array_key_exists('extension', $localPathInfo) && 'twig' === $localPathInfo['extension']) {
            $distPath = dirname($distPath).'/'.basename($distPath, '.twig');
            file_put_contents($distPath, $this->twig->render($localPath, $branchConfig));
        } else {
            reset($projectConfig['branches']);
            $unstableBranch = key($projectConfig['branches']);
            $stableBranch = next($projectConfig['branches']) ? key($projectConfig['branches']) : $unstableBranch;
            file_put_contents($distPath, str_replace(array(
                '{{ unstable_branch }}',
                '{{ stable_branch }}',
                '{{ docs_path }}',
            ), array(
                $unstableBranch,
                $stableBranch,
                $branchConfig['docs_path'],
            ), $localContent));
        }
    }
}
