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

use App\Config\ProjectsConfigurations;
use App\Domain\Value\Project;
use Github\Client;
use Github\Exception\ExceptionInterface;
use Github\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class AutoMergeCommand extends Command
{
    private SymfonyStyle $io;
    private bool $apply;
    private ProjectsConfigurations $projectsConfigurations;
    private LoggerInterface $logger;

    public function __construct(ProjectsConfigurations $projectsConfigurations, Client $github, LoggerInterface $logger)
    {
        parent::__construct();

        $this->projectsConfigurations = $projectsConfigurations;
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
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applies wanted requests')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->io = new SymfonyStyle($input, $output);

        $this->apply = $input->getOption('apply');
        if (!$this->apply) {
            $this->io->warning('This is a dry run execution. No change will be applied here.');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projects = $this->projectsConfigurations->all();

        if ([] !== $input->getArgument('projects')) {
            $projects = $this->projectsConfigurations->byNames($input->getArgument('projects'));
        }

        /** @var Project $project */
        foreach ($projects as $project) {
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
                $response = $this->githubClient->repo()->merge(
                    static::GITHUB_GROUP,
                    $repository->name(),
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
