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
use App\Github\Api\Repositories;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchSettingsCommand extends AbstractNeedApplyCommand
{
    private Projects $projects;
    private Repositories $repositories;

    public function __construct(Projects $projects, Repositories $repositories)
    {
        parent::__construct();

        $this->projects = $projects;
        $this->repositories = $repositories;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:settings')
            ->setDescription('Dispatches repository information and general settings for all sonata projects.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Dispatch repository information and general settings for all sonata projects');

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->title($project->name());

                $this->updateRepositories($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateRepositories(Project $project): void
    {
        $repository = $project->repository();

        $repositoryInfo = $this->repositories->show($repository);

        $infoToUpdate = [
            'allow_merge_commit' => false,
            'allow_rebase_merge' => true,
            'allow_squash_merge' => true,
            'delete_branch_on_merge' => true,
            'description' => $project->description(),
            'has_issues' => true,
            'has_projects' => true,
            'has_wiki' => false,
            'homepage' => $project->homepage(),
            'is_template' => false,
        ];

        if ($project->hasBranches()) {
            $infoToUpdate['default_branch'] = $project->defaultBranch()->name();
        }

        foreach ($infoToUpdate as $info => $value) {
            if ($value === $repositoryInfo[$info]) {
                unset($infoToUpdate[$info]);
            }
        }

        if (\count($infoToUpdate)) {
            $this->io->writeln('    Following info have to be changed:');

            foreach ($infoToUpdate as $info => $value) {
                $this->io->writeln(sprintf(
                    '        %s: <info>%s</info>',
                    $info,
                    $value
                ));
            }

            if ($this->apply) {
                $this->repositories->update(
                    $repository,
                    $infoToUpdate
                );
            }
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }
}
