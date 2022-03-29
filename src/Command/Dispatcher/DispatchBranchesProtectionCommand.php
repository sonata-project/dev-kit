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
use App\Domain\Value\Branch;
use App\Domain\Value\Project;
use App\Github\Api\BranchProtections;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchBranchesProtectionCommand extends AbstractNeedApplyCommand
{
    private Projects $projects;
    private BranchProtections $branchProtections;

    public function __construct(Projects $projects, BranchProtections $branchProtections)
    {
        parent::__construct();

        $this->projects = $projects;
        $this->branchProtections = $branchProtections;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:branches-protection')
            ->setDescription('Dispatches branches protection for all sonata projects.')
            ->addArgument('projects', InputArgument::IS_ARRAY, 'To limit the dispatcher on given project(s).', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projects = $this->projects->all();
        $title = 'Dispatch branches protection for all sonata projects';

        /** @var string[] $projectNames */
        $projectNames = $input->getArgument('projects');
        if ([] !== $projectNames) {
            $projects = $this->projects->byNames($projectNames);
            $title = sprintf(
                'Dispatch branches protection for: %s',
                implode(', ', $projectNames)
            );
        }

        $this->io->title($title);

        foreach ($projects as $project) {
            try {
                $this->io->section($project->name());

                $this->updateBranchesProtection($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateBranchesProtection(Project $project): void
    {
        $repository = $project->repository();

        foreach ($project->branches() as $branch) {
            $requiredStatusChecks = $this->buildRequiredStatusChecks(
                $project,
                $branch
            );

            if ($this->apply) {
                $settings = [
                    'required_status_checks' => [
                        'strict' => false,
                        'contexts' => $requiredStatusChecks,
                    ],
                    'required_pull_request_reviews' => [
                        'dismissal_restrictions' => [
                            'users' => [],
                            'teams' => [],
                        ],
                        'dismiss_stale_reviews' => true,
                        'require_code_owner_reviews' => true,
                    ],
                    'restrictions' => null,
                    'enforce_admins' => false,
                ];

                if ('sandbox' === $project->name()) {
                    /*
                     * Otherwise automerge for dependabot PRs will not work.
                     */
                    $settings['required_pull_request_reviews'] = null;
                }

                $this->branchProtections->update(
                    $repository,
                    $branch,
                    $settings
                );
            }
        }

        if ($this->apply) {
            $this->io->comment('Branches protection applied.');
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    /**
     * @return string[]
     */
    private function buildRequiredStatusChecks(Project $project, Branch $branch): array
    {
        $lowestPhpVersion = $branch->lowestPhpVersion();
        $requiredStatusChecks = [
            'Composer',
            'YAML files',
            'XML files',
            'PHP-CS-Fixer',
            'PHPStan',
            'Psalm',
            sprintf(
                'PHP %s + lowest + normal',
                $lowestPhpVersion->toString()
            ),
        ];

        if ($project->hasDocumentation()) {
            $requiredStatusChecks[] = 'Sphinx build';
            $requiredStatusChecks[] = 'DOCtor-RST';
        }

        if ($branch->hasFrontend()) {
            $requiredStatusChecks[] = 'Webpack Encore';
        }

        if ($project->hasTestKernel()) {
            $requiredStatusChecks[] = 'Symfony container';
            $requiredStatusChecks[] = 'Twig files';
            $requiredStatusChecks[] = 'XLIFF files';
            $requiredStatusChecks[] = 'YML files';
        }

        if ($project->rector()) {
            $requiredStatusChecks[] = 'Rector';
        }

        foreach ($branch->phpVersions() as $phpVersion) {
            $requiredStatusChecks[] = sprintf(
                'PHP %s + highest + normal',
                $phpVersion->toString()
            );
        }

        $targetPhp = $branch->targetPhpVersion();
        foreach ($branch->variants() as $variant) {
            $requiredStatusChecks[] = sprintf(
                'PHP %s + highest + %s',
                $targetPhp->toString(),
                $variant->toString()
            );
        }

        $this->io->writeln(sprintf(
            'Required Status-Checks for <info>%s</info>:',
            $branch->name()
        ));
        $this->io->listing($requiredStatusChecks);

        return $requiredStatusChecks;
    }
}
