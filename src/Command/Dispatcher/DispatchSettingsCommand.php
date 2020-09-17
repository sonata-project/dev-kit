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
use Packagist\Api\Result\Package;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;
use function Symfony\Component\String\u;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchSettingsCommand extends AbstractNeedApplyCommand
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
            ->setName('dispatch:settings')
            ->setDescription('Dispatches repository information and general settings for all sonata projects.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Dispatch repository information and general settings for all sonata projects');

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->title($project->name());

                if ('doctrine-phpcr-admin-bundle' !== $project->name()) {
                    continue;
                }

                $this->updateRepositories($project);
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

    private function updateRepositories(Project $project): void
    {
        $repository = $project->repository();
        $latestVersion = $this->getLatestPackageVersion($project->package());

        $repositoryInfo = $this->github->repo()->show(
            $repository->vendor(),
            $repository->name()
        );

        $infoToUpdate = [
            'homepage' => $latestVersion->getHomepage() ?: 'https://sonata-project.org',
            'description' => $latestVersion->isAbandoned()
                ? '[Abandonned] '.$latestVersion->getDescription()
                : $latestVersion->getDescription(),
            'has_issues' => true,
            'has_projects' => true,
            'has_wiki' => false,
            'allow_squash_merge' => true,
            'allow_merge_commit' => false,
            'allow_rebase_merge' => true,
        ];

        $branchNames = $project->branchNames();
        $defaultBranch = end($branchNames);

        if (is_string($defaultBranch)) {
            $infoToUpdate['default_branch'] = $defaultBranch;
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
                $this->github->repo()->update($repository->vendor(), $repository->name(), array_merge($infoToUpdate, [
                    'name' => $repository->name(),
                ]));

                if ([] !== $latestVersion->getKeywords()) {
                    $infoToUpdate['topics'] = $latestVersion->getKeywords();
                }
            }
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    private function updateTopics(Project $project): void
    {
        $repository = $project->repository();
        $latestVersion = $this->getLatestPackageVersion($project->package());

        $topics = $this->github->repo()->topics(
            $repository->vendor(),
            $repository->name()
        );
        Assert::keyExists($topics, 'names');

        $keywords = $latestVersion->getKeywords();
        \assert(is_array($keywords));

        natsort($keywords);

        $keywords = array_map(static function(string $keyword): string {
            return u($keyword)->lower()->replace(' ', '-')->toString();
        }, $keywords);

        if ([] !== array_diff($topics['names'], $keywords)) {
            $this->io->writeln('    Following topics have to be set:');
            $this->io->writeln(sprintf(
                '        <info>%s</info>',
                implode(', ', $keywords),
            ));

            if ($this->apply) {
                $this->github->repo()->replaceTopics(
                    $repository->vendor(),
                    $repository->name(),
                    $keywords
                );
            }
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }

    /**
     * Return the latest packagist version.
     */
    private function getLatestPackageVersion(Package $package): Package\Version
    {
        $versions = $package->getVersions();
        $lastVersion = reset($versions);

        if (false === $lastVersion) {
            // This package was never released, we create a fake empty version
            return new Package\Version();
        }

        return $lastVersion;
    }
}
