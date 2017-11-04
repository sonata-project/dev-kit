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
use Symfony\Component\Console\Input\InputOption;
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
            ->addArgument('projects', InputArgument::IS_ARRAY, 'To limit the dispatcher on given project(s).', [])
            ->addOption('with-files', null, InputOption::VALUE_NONE, 'Applies Pull Request actions for projects files');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->gitWrapper = new GitWrapper();
        $this->fileSystem = new Filesystem();
        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem(__DIR__.'/../../..'));

        $this->projects = count($input->getArgument('projects'))
            ? $input->getArgument('projects')
            : array_keys($this->configs['projects']);
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
                $projectConfig = $this->configs['projects'][$name];
                $this->io->title($package->getName());
                $this->updateRepositories($package, $projectConfig);
                $this->updateDevKitHook($package);
                $this->updateLabels($package);
                $this->updateBranchesProtection($package, $projectConfig);

                if ($input->getOption('with-files')) {
                    $this->dispatchFiles($package);
                }
            } catch (ExceptionInterface $e) {
                $this->io->error('Failed with message: '.$e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Sets repository information and general settings.
     *
     * @param Package $package
     * @param array   $projectConfig
     */
    private function updateRepositories(Package $package, array $projectConfig)
    {
        $repositoryName = $this->getRepositoryName($package);
        $branches = array_keys($projectConfig['branches']);
        $this->io->section('Repository');

        $repositoryInfo = $this->githubClient->repo()->show(static::GITHUB_GROUP, $repositoryName);
        $infoToUpdate = [
            'homepage'           => 'https://sonata-project.org/',
            'has_issues'         => true,
            'has_projects'       => true,
            'has_wiki'           => false,
            'default_branch'     => end($branches),
            'allow_squash_merge' => true,
            'allow_merge_commit' => false,
            'allow_rebase_merge' => true,
        ];

        foreach ($infoToUpdate as $info => $value) {
            if ($value === $repositoryInfo[$info]) {
                unset($infoToUpdate[$info]);
            }
        }

        if (count($infoToUpdate)) {
            $this->io->comment('Following info have to be changed: '.implode(', ', array_keys($infoToUpdate)).'.');
            if ($this->apply) {
                $this->githubClient->repo()->update(static::GITHUB_GROUP, $repositoryName, array_merge($infoToUpdate, [
                    'name' => $repositoryName,
                ]));
            }
        } elseif (!count($infoToUpdate)) {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
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

        $headers = ['Name', 'Actual color', 'Needed Color', 'State'];
        $rows = [];

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
                    $this->githubClient->repo()->labels()->update(static::GITHUB_GROUP, $repositoryName, $name, [
                        'name'  => $name,
                        'color' => $configuredColor,
                    ]);
                }
            }

            if ($state) {
                array_push($rows, [
                    $name,
                    '#'.$color,
                    $configuredColor ? '#'.$configuredColor : 'N/A',
                    $state,
                ]);
            }
        }

        foreach ($missingLabels as $name => $label) {
            $color = $label['color'];

            if ($this->apply) {
                $this->githubClient->repo()->labels()->create(static::GITHUB_GROUP, $repositoryName, [
                    'name'  => $name,
                    'color' => $color,
                ]);
            }
            array_push($rows, [$name, 'N/A', '#'.$color, 'Created']);
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

    private function updateDevKitHook(Package $package)
    {
        $repositoryName = $this->getRepositoryName($package);
        $this->io->section('DevKit hook');

        // Construct the hook url.
        $hookToken = getenv('DEK_KIT_TOKEN') ? getenv('DEK_KIT_TOKEN') : 'INVALID_TOKEN';
        $hookBaseUrl = 'http://sonata-dev-kit.sullivansenechal.com/github';
        $hookCompleteUrl = $hookBaseUrl.'?'.http_build_query(['token' => $hookToken]);

        // Set hook configs
        $config = [
            'url'          => $hookCompleteUrl,
            'insecure_ssl' => '0',
            'content_type' => 'json',
        ];
        $events = [
            'issue_comment',
            'pull_request',
            'pull_request_review_comment',
        ];

        // First, check if the hook exists.
        $devKitHook = null;
        foreach ($this->githubClient->repo()->hooks()->all(static::GITHUB_GROUP, $repositoryName) as $hook) {
            if (array_key_exists('url', $hook['config'])
                && 0 === strncmp($hook['config']['url'], $hookBaseUrl, strlen($hookBaseUrl))) {
                $devKitHook = $hook;

                break;
            }
        }

        if (!$devKitHook) {
            $this->io->comment('Has to be created.');

            if ($this->apply) {
                $this->githubClient->repo()->hooks()->create(static::GITHUB_GROUP, $repositoryName, [
                    'name'   => 'web',
                    'config' => $config,
                    'events' => $events,
                    'active' => true,
                ]);
                $this->io->success('Hook created.');
            }
        } elseif (count(array_diff_assoc($devKitHook['config'], $config))
            || count(array_diff($devKitHook['events'], $events))
            || !$devKitHook['active']
        ) {
            $this->io->comment('Has to be updated.');

            if ($this->apply) {
                $this->githubClient->repo()->hooks()->update(static::GITHUB_GROUP, $repositoryName, $devKitHook['id'], [
                    'name'   => 'web',
                    'config' => $config,
                    'events' => $events,
                    'active' => true,
                ]);
                $this->githubClient->repo()->hooks()->ping(static::GITHUB_GROUP, $repositoryName, $devKitHook['id']);
                $this->io->success('Hook updated.');
            }
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    /**
     * @param Package $package
     * @param array   $projectConfig
     */
    private function updateBranchesProtection(Package $package, array $projectConfig)
    {
        $repositoryName = $this->getRepositoryName($package);
        $branches = array_keys($projectConfig['branches']);
        $this->io->section('Branches protection');

        if (!$this->apply) {
            return;
        }

        $protectionConfig = [
            'required_status_checks' => [
                'strict'   => false,
                'contexts' => [
                    'continuous-integration/travis-ci',
                    'continuous-integration/styleci/pr',
                ],
            ],
            'required_pull_request_reviews' => [
                'dismissal_restrictions' => [
                    'users' => [],
                    'teams' => [],
                ],
                'dismiss_stale_reviews'      => true,
                'require_code_owner_reviews' => true,
            ],
            'restrictions'   => null,
            'enforce_admins' => false,
        ];
        foreach ($branches as $branch) {
            $this->githubClient->repo()->protection()
                ->update(static::GITHUB_GROUP, $repositoryName, $branch, $protectionConfig);
        }
        $this->io->comment('Branches protection applied.');
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
            ->config('user.email', static::GITHUB_EMAIL);

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

            $git->reset(['hard' => true]);

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

            $git->add('.', ['all' => true])->getOutput();
            $diff = $git->diff('--color', '--cached')->getOutput();

            if (!empty($diff)) {
                $this->io->writeln($diff);
                if ($this->apply) {
                    $git->commit('DevKit updates')->push('-u', 'origin', $currentDevKit);

                    // If the Pull Request does not exists yet, create it.
                    $pulls = $this->githubClient->pullRequests()->all(static::GITHUB_GROUP, $repositoryName, [
                        'state' => 'open',
                        'head'  => 'sonata-project:'.$currentDevKit,
                    ]);
                    if (0 === count($pulls)) {
                        $this->githubClient->pullRequests()->create(static::GITHUB_GROUP, $repositoryName, [
                            'title' => 'DevKit updates for '.$currentBranch.' branch',
                            'head'  => 'sonata-project:'.$currentDevKit,
                            'base'  => $currentBranch,
                            'body'  => '',
                        ]);
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
                if (!in_array($entry, ['.', '..'], true)) {
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
                $branchConfig,
                ['repository_name' => $repositoryName]
            )));
        } else {
            reset($projectConfig['branches']);
            $unstableBranch = key($projectConfig['branches']);
            $stableBranch = next($projectConfig['branches']) ? key($projectConfig['branches']) : $unstableBranch;
            file_put_contents($distPath, str_replace([
                '{{ package_title }}',
                '{{ package_description }}',
                '{{ packagist_name }}',
                '{{ repository_name }}',
                '{{ current_branch }}',
                '{{ unstable_branch }}',
                '{{ stable_branch }}',
                '{{ docs_path }}',
                '{{ tests_path }}',
                '{{ website_path }}',
            ], [
                Inflector::ucwords(str_replace(['-project', '/', '-'], ['', ' ', ' '], $package->getName())),
                $package->getDescription(),
                $package->getName(),
                $repositoryName,
                $branchName,
                $unstableBranch,
                $stableBranch,
                $branchConfig['docs_path'],
                $branchConfig['tests_path'],
                str_replace([static::PACKAGIST_GROUP.'/', '-bundle'], '', $package->getName()),
            ], $localContent));
        }
        // Restore file permissions after content copy
        $this->fileSystem->chmod($distPath, fileperms($localPath));
    }
}
