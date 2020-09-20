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

use App\Config\Projects;
use App\Domain\Value\Project;
use App\Github\Api\Repositories;
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
    private Projects $projects;
    private Repositories $repositories;
    private LoggerInterface $logger;

    public function __construct(Projects $projects, Repositories $repositories, LoggerInterface $logger)
    {
        parent::__construct();

        $this->projects = $projects;
        $this->repositories = $repositories;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projects = $this->projects->all();

        $title = 'Merge branches of repositories if there is no conflict';
        if ([] !== $input->getArgument('projects')) {
            $projects = $this->projects->byNames($input->getArgument('projects'));
            $title = sprintf(
                '%s for: %s',
                $title,
                implode(', ', $input->getArgument('projects'))
            );
        }

        $this->io->title($title);

        /** @var Project $project */
        foreach ($projects as $project) {
            try {
                $this->io->section($project->name());

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
        $branchNames = $project->branchNamesReverse();

        // Merge the oldest branch into the next newest, and so on.
        while (($head = current($branchNames))) {
            $base = next($branchNames);
            if (false === $base) {
                break;
            }

            try {
                $response = $this->repositories->merge(
                    $repository,
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
                        $repository->name(),
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
