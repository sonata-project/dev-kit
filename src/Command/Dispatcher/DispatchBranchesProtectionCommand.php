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
use App\Util\Util;
use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use Packagist\Api\Result\Package;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchBranchesProtectionCommand extends AbstractNeedApplyCommand
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
            ->setName('dispatch:branches-protection')
            ->setDescription('Dispatches branches protection for all sonata projects.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Dispatch branches protection for all sonata projects');

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->section($project->name());

                $this->updateBranchesProtection(
                    $project->package(),
                    $project->rawConfig()
                );
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateBranchesProtection(Package $package, array $projectConfig): void
    {
        $repositoryName = Util::getRepositoryNameWithoutVendorPrefix($package);
        $branches = array_keys($projectConfig['branches']);

        foreach ($branches as $branch) {
            $this->io->writeln(sprintf(
                '<info>%s</info>',
                $branch
            ));

            $requiredStatusChecks = $this->buildRequiredStatusChecks(
                $branch,
                $projectConfig['branches'][$branch],
                $projectConfig['docs_target']
            );

            if ($this->apply) {
                $this->github->repo()->protection()
                    ->update(static::GITHUB_GROUP, $repositoryName, $branch, [
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
                    ]);
            }
        }

        if ($this->apply) {
            $this->io->comment('Branches protection applied.');
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    private function buildRequiredStatusChecks(string $branchName, array $branchConfig, bool $docsTarget): array
    {
        $targetPhp = $branchConfig['target_php'] ?? end($branchConfig['php']);
        $requiredStatusChecks = [
            'composer-normalize',
            'YAML files',
            'XML files',
            'PHP-CS-Fixer',
            sprintf('PHP %s + lowest + normal', reset($branchConfig['php'])),
        ];

        if ($docsTarget) {
            $requiredStatusChecks[] = 'Sphinx build';
            $requiredStatusChecks[] = 'DOCtor-RST';
        }

        foreach ($branchConfig['php'] as $phpVersion) {
            $requiredStatusChecks[] = sprintf('PHP %s + highest + normal', $phpVersion);
        }

        foreach ($branchConfig['variants'] as $variant => $versions) {
            foreach ($versions as $version) {
                $requiredStatusChecks[] = sprintf(
                    'PHP %s + highest + %s:"%s"',
                    $targetPhp,
                    $variant,
                    'dev-master' === $version ? $version : ($version.'.*'),
                );
            }
        }

        $this->io->writeln(sprintf(
            'Required Status-Checks for <info>%s</info>:',
            $branchName
        ));
        $this->io->listing($requiredStatusChecks);

        return $requiredStatusChecks;
    }
}
