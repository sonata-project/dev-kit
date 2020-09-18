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

namespace App\Command\Dispatcher;

use App\Command\AbstractNeedApplyCommand;
use App\Config\Projects;
use App\Domain\Value\Project;
use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchTopicsCommand extends AbstractNeedApplyCommand
{
    private Projects $projects;
    private GithubClient $github;

    public function __construct(Projects $projects, GithubClient $github)
    {
        parent::__construct();

        $this->projects = $projects;
        $this->github = $github;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:topics')
            ->setDescription('Dispatches repository topics for all sonata projects.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Dispatch repository topics for all sonata projects');

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->title($project->name());

                $this->updateTopics($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateTopics(Project $project): void
    {
        $repository = $project->repository();

        $topics = $this->github->repo()->topics(
            $repository->vendor(),
            $repository->name()
        );
        Assert::keyExists($topics, 'names');
        $topics = $topics['names'];

        if ([] === $topics || [] === $project->topics()) {
            $this->io->writeln(sprintf(
                '    <comment>%s</comment>',
                'No topics are currently set on the repository, nor new ones are configured!'
            ));

            return;
        }

        if ([] !== array_diff($topics, $project->topics())) {
            $this->io->writeln('    Topics would be changed...');
            $this->io->writeln(sprintf(
                '        from <comment>%s</comment>',
                implode(', ', $topics),
            ));
            $this->io->writeln(sprintf(
                '        to   <info>%s</info>',
                implode(', ', $project->topics()),
            ));

            if ($this->apply) {
                $this->github->repo()->replaceTopics(
                    $repository->vendor(),
                    $repository->name(),
                    $project->topics()
                );
            }
        } else {
            $this->io->writeln('    Topics are up to date!');
        }
    }
}
