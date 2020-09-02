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

use App\Domain\Value\Project;
use Github\Exception\ExceptionInterface;
use Github\Exception\RuntimeException;
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
     * @var array<string, Project>
     */
    private $projects = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct();
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

        $selectedProjects = $input->getArgument('projects');

        foreach ($this->configs['projects'] as $name => $config) {
            if ($selectedProjects > 0
                && !\in_array($name, $selectedProjects, true)
            ) {
                continue;
            }

            $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);

            $this->projects[$name] = Project::fromValues($name, $config, $package);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Project $project */
        foreach ($this->projects as $project) {
            try {
                $this->io->title($project->name());

                $this->mergeBranches($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function mergeBranches(Project $project): void
    {
        if (!$this->apply || !$project->hasBranches()) {
            return;
        }

        $repository = $project->repository();

        $branches = array_reverse($project->branchNames());

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
                    $repository->packageName(),
                    $base,
                    $head,
                    sprintf('Merge %s into %s', $head, $base)
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
                        $repository->packageName(),
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
