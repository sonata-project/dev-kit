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
use Github\Exception\RuntimeException;
use Packagist\Api\Result\Package;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class AutoMergeCommand extends AbstractNeedApplyCommand
{
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
            ->setName('auto-merge')
            ->setDescription('Merges branches of repositories if there is no conflict.')
            ->addArgument('projects', InputArgument::IS_ARRAY, 'To limit the dispatcher on given project(s).', []);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

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
            $projectConfig = $this->configs['projects'][$name];

            try {
                $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);
                $this->io->title($package->getName());
                $this->mergeBranches($package, $projectConfig);
            } catch (ExceptionInterface $e) {
                $this->io->error('Failed with message: '.$e->getMessage());
            }
        }

        return 0;
    }

    private function mergeBranches(Package $package, array $projectConfig)
    {
        if (!$this->apply || !array_key_exists('branches', $projectConfig)) {
            return;
        }

        $repositoryName = $this->getRepositoryName($package);
        $branches = array_reverse(array_keys($projectConfig['branches']));

        // Merge the oldest branch into the next newest, and so on.
        while (($head = current($branches))) {
            $base = next($branches);
            if (false === $base) {
                break;
            }

            try {
                // Merge message should be removed when following PR will be merged and tagged.
                // https://github.com/KnpLabs/php-github-api/pull/379
                $response = $this->githubClient->repo()->merge(
                    static::GITHUB_GROUP,
                    $repositoryName,
                    $base,
                    $head,
                    sprintf('Merge %s into %s', $head, $base)
                );

                if (is_array($response) && array_key_exists('sha', $response)) {
                    $this->io->success(sprintf('Merged %s into %s', $head, $base));
                } else {
                    $this->io->comment('Nothing to merge on '.$base);
                }
            } catch (RuntimeException $e) {
                if (409 === $e->getCode()) {
                    $message = sprintf('Merging of %s into %s contains conflicts. Skipped.', $head, $base);

                    $this->io->warning($message);
                    $this->slackClient->attach([
                        'text'  => $message,
                        'color' => 'danger',
                    ])->send('Merging: '.$repositoryName);

                    continue;
                }

                throw $e;
            }
        }
    }
}
