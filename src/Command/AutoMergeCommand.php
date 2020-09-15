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

use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use Github\Exception\RuntimeException;
use Packagist\Api\Client as PackagistClient;
use Packagist\Api\Result\Package;
use Psr\Log\LoggerInterface;
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

    private PackagistClient $packagist;
    private GithubClient $github;
    private LoggerInterface $logger;

    public function __construct(PackagistClient $packagist, GithubClient $github, LoggerInterface $logger)
    {
        parent::__construct();

        $this->packagist = $packagist;
        $this->github = $github;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('auto-merge')
            ->setDescription('Merges branches of repositories if there is no conflict.')
            ->addArgument('projects', InputArgument::IS_ARRAY, 'To limit the dispatcher on given project(s).', [])
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->projects = \count($input->getArgument('projects'))
            ? $input->getArgument('projects')
            : array_keys($this->configs['projects'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $notConfiguredProjects = array_diff($this->projects, array_keys($this->configs['projects']));
        if (\count($notConfiguredProjects)) {
            $this->io->error(sprintf(
                'Some specified projects are not configured: %s',
                implode(', ', $notConfiguredProjects)
            ));

            return 1;
        }

        foreach ($this->projects as $name) {
            $projectConfig = $this->configs['projects'][$name];

            try {
                $package = $this->packagist->get(static::PACKAGIST_GROUP.'/'.$name);
                $this->io->title($package->getName());
                $this->mergeBranches($package, $projectConfig);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function mergeBranches(Package $package, array $projectConfig): void
    {
        if (!$this->apply || !\array_key_exists('branches', $projectConfig)) {
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
                $response = $this->github->repo()->merge(
                    static::GITHUB_GROUP,
                    $repositoryName,
                    $base,
                    $head
                );

                if (\is_array($response) && \array_key_exists('sha', $response)) {
                    $this->io->success(sprintf(
                        'Merged %s into %s',
                        $head,
                        $base
                    ));
                } else {
                    $this->io->comment(sprintf(
                        'Nothing to merge on %s',
                        $base
                    ));
                }
            } catch (RuntimeException $e) {
                if (409 === $e->getCode()) {
                    $message = sprintf(
                        '%s: Merging of %s into %s contains conflicts. Skipped.',
                        $repositoryName,
                        $head,
                        $base
                    );

                    $this->io->warning($message);
                    $this->logger->warning($message);

                    continue;
                }

                throw $e;
            }
        }
    }
}
