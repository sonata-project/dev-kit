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

use Doctrine\Common\Inflector\Inflector;
use Github\Exception\ExceptionInterface;
use GitWrapper\GitWrapper;
use Packagist\Api\Result\Package;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class DispatchCommand extends AbstractNeedApplyCommand
{
    const LABEL_NOTHING_CHANGED = 'Nothing to be changed.';

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
     * @var string[]
     */
    private $projects;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('dispatch')
            ->setDescription('Dispatches configuration and documentation files for all sonata projects.')
            ->addArgument('projects', InputArgument::IS_ARRAY, 'To limit the dispatcher on given project(s).', array())
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->gitWrapper = new GitWrapper();
        $this->fileSystem = new Filesystem();
        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__.'/../../..'));

        $this->projects = count($input->getArgument('projects'))
            ? $input->getArgument('projects')
            : array_keys($this->configs['projects'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $notConfiguredProjects = array_diff($this->projects, array_keys($this->configs['projects']));
        if (count($notConfiguredProjects)) {
            $this->io->error('Some specified projects are not configured: '.implode(', ', $notConfiguredProjects));

            return 1;
        }

        foreach ($this->projects as $name) {
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
                        'name' => $name,
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
                    'name' => $name,
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

        $branches = array_reverse($projectConfig['branches']);

        $previousBranch = null;
        $previousDevKit = null;
        while (($branchConfig = current($branches))) {
            // We have to fetch all branches on each step in case a PR is submitted.
            $remoteBranches = array_map(function ($branch) {
                return $branch['name'];
            }, $this->githubClient->repos()->branches(static::GITHUB_GROUP, $repositoryName));

            $currentBranch = key($branches);
            $currentDevKit = $currentBranch.'-dev-kit';
            next($branches);

            // A PR is already here for previous branch, do nothing on the current one.
            if (in_array($previousDevKit, $remoteBranches, true)) {
                continue;
            }
            // If the previous branch is not merged into the current one, do nothing.
            if ($previousBranch && $this->githubClient->repos()->commits()->compare(
                    static::GITHUB_GROUP, $repositoryName, $currentBranch, $previousBranch
                )['ahead_by']) {
                continue;
            }

            // Diff application
            $this->io->section('Files for '.$currentBranch);

            $git->reset(array('hard' => true));

            // Checkout the targeted branch
            if (in_array($currentBranch, $git->getBranches()->all(), true)) {
                $git->checkout($currentBranch);
            } else {
                $git->checkout('-b', $currentBranch, '--track', 'origin/'.$currentBranch);
            }
            // Checkout the dev-kit branch
            if (in_array('remotes/origin/'.$currentDevKit, $git->getBranches()->all(), true)) {
                $git->checkout('-b', $currentDevKit, '--track', 'origin/'.$currentDevKit);
            } else {
                $git->checkout('-b', $currentDevKit);
            }

            $this->renderFile($package, $repositoryName, 'project', $clonePath, $projectConfig, $currentBranch);

            $git->add('.', array('all' => true))->getOutput();
            $diff = $git->diff('--color', '--cached')->getOutput();

            if (!empty($diff)) {
                $this->io->writeln($diff);
                if ($this->apply) {
                    $git->commit('DevKit updates')->push('-u', 'origin', $currentDevKit);

                    // If the Pull Request does not exists yet, create it.
                    $pulls = $this->githubClient->pullRequests()->all(static::GITHUB_GROUP, $repositoryName, array(
                        'state' => 'open',
                        'head' => 'sonata-project:'.$currentDevKit,
                    ));
                    if (0 === count($pulls)) {
                        $this->githubClient->pullRequests()->create(static::GITHUB_GROUP, $repositoryName, array(
                            'title' => 'DevKit updates for '.$currentBranch.' branch',
                            'head' => 'sonata-project:'.$currentDevKit,
                            'base' => $currentBranch,
                            'body' => '',
                        ));
                    }

                    // Wait 200ms to be sure GitHub API is up to date with new pushed branch/PR.
                    usleep(200000);
                }
            } else {
                $this->io->comment(static::LABEL_NOTHING_CHANGED);
            }

            // Save the current branch to the previous and go to next step
            $previousBranch = $currentBranch;
            $previousDevKit = $currentDevKit;
        }
    }

    /**
     * @param Package $package
     * @param string  $repositoryName
     * @param string  $localPath
     * @param string  $distPath
     * @param array   $projectConfig
     * @param string  $branchName
     */
    private function renderFile(Package $package, $repositoryName, $localPath, $distPath, array $projectConfig, $branchName)
    {
        $localFullPath = __DIR__.'/../../../'.$localPath;
        $localFileType = filetype($localFullPath);
        $distFileType = $this->fileSystem->exists($distPath) ? filetype($distPath) : false;

        if ($localFileType !== $distFileType && false !== $distFileType) {
            throw new \LogicException('File type mismatch between "'.$localPath.'" and "'.$distPath.'"');
        }

        if (in_array(substr($localPath, 8), $projectConfig['excluded_files'], true)) {
            return;
        }

        if ('dir' === $localFileType) {
            $localDirectory = dir($localFullPath);
            while (false !== ($entry = $localDirectory->read())) {
                if (!in_array($entry, array('.', '..'), true)) {
                    $this->renderFile(
                        $package,
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
            file_put_contents($distPath, $this->twig->render($localPath, array_merge(
                $this->configs,
                $projectConfig,
                $branchConfig
            )));
        } else {
            reset($projectConfig['branches']);
            $unstableBranch = key($projectConfig['branches']);
            $stableBranch = next($projectConfig['branches']) ? key($projectConfig['branches']) : $unstableBranch;
            file_put_contents($distPath, str_replace(array(
                '{{ package_title }}',
                '{{ package_description }}',
                '{{ packagist_name }}',
                '{{ repository_name }}',
                '{{ current_branch }}',
                '{{ unstable_branch }}',
                '{{ stable_branch }}',
                '{{ docs_path }}',
                '{{ website_path }}',
            ), array(
                Inflector::ucwords(str_replace(array('-project', '/', '-'), array('', ' ', ' '), $package->getName())),
                $package->getDescription(),
                $package->getName(),
                $repositoryName,
                $branchName,
                $unstableBranch,
                $stableBranch,
                $branchConfig['docs_path'],
                str_replace(array(static::PACKAGIST_GROUP.'/', '-bundle'), '', $package->getName()),
            ), $localContent));
        }
        // Restore file permissions after content copy
        $this->fileSystem->chmod($distPath, fileperms($localPath));
    }
}
