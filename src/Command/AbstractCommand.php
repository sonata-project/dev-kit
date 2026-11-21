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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    public const GITHUB_USER = 'SonataCI';
    public const GITHUB_EMAIL = 'thomas+ci@sonata-project.org';
    public const DEPENDABOT_BOT = 'dependabot[bot]';

    protected const LABEL_NOTHING_CHANGED = 'Nothing to be changed.';

    protected SymfonyStyle $io; // @phpstan-ignore-line

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Runs the callback for each project. A GitHub API error only fails its own project, the
     * remaining ones are still processed. The failures are summarized at the end and turn the
     * exit code into a failure, so that CI does not report a green run when nothing worked.
     *
     * @param array<Project>          $projects
     * @param callable(Project): void $callback
     */
    protected function processProjects(array $projects, callable $callback): int
    {
        $failures = [];

        foreach ($projects as $project) {
            $this->io->section($project->name());

            try {
                $callback($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(\sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));

                $failures[] = [$project->name(), $e->getMessage()];
            }
        }

        if ([] === $failures) {
            return self::SUCCESS;
        }

        $this->io->section('Summary');
        $this->io->table(['Project', 'Error'], $failures);
        $this->io->error(\sprintf(
            '%d of %d project(s) failed.',
            \count($failures),
            \count($projects)
        ));

        return self::FAILURE;
    }
}
