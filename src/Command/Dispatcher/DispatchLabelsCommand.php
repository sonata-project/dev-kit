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
use App\Config\ConfiguredLabels;
use App\Config\Projects;
use App\Domain\Value\Project;
use App\Github\Api\Labels;
use App\Github\Domain\Value\Label;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchLabelsCommand extends AbstractNeedApplyCommand
{
    private Projects $projects;
    private ConfiguredLabels $configuredLabels;
    private Labels $labels;

    public function __construct(
        Projects $projects,
        ConfiguredLabels $configuredLabels,
        Labels $labels
    ) {
        parent::__construct();

        $this->projects = $projects;
        $this->configuredLabels = $configuredLabels;
        $this->labels = $labels;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:labels')
            ->setDescription('Dispatches labels for all sonata projects.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Dispatch labels for all sonata projects');

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->section($project->name());

                $this->updateLabels($project);
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateLabels(Project $project): void
    {
        $repository = $project->repository();

        $missingLabels = $this->configuredLabels->all();

        $headers = [
            'Name',
            'Actual color',
            'Needed color',
            'State',
        ];

        $rows = [];

        foreach ($this->labels->all($repository) as $remoteLabel) {
            $name = $remoteLabel->name();

            $shouldExist = $this->configuredLabels->shouldExist($remoteLabel);

            if ($shouldExist) {
                unset($missingLabels[$name]);
            }

            $shouldBeUpdated = $this->configuredLabels->needsUpdate($remoteLabel);

            if (!$shouldExist) {
                if ($this->apply) {
                    $this->labels->remove($repository, $remoteLabel);
                }

                $rows[] = [
                    $name,
                    $remoteLabel->color()->asHexCode(),
                    '',
                    'DELETE',
                ];
            } elseif ($shouldBeUpdated) {
                $configuredLabel = $this->configuredLabels->byName($remoteLabel->name());

                if ($this->apply) {
                    $this->labels->update($repository, $remoteLabel, [
                        'name' => $name,
                        'color' => $configuredLabel->color()->toString(),
                    ]);
                }

                $rows[] = [
                    $name,
                    $remoteLabel->color()->asHexCode(),
                    $configuredLabel->color()->asHexCode(),
                    'UPDATE',
                ];
            }
        }

        /** @var Label $label */
        foreach ($missingLabels as $label) {
            if ($this->apply) {
                $this->labels->create($repository, $label);
            }

            $rows[] = [
                $label->name(),
                '',
                $label->color()->asHexCode(),
                'CREATE',
            ];
        }

        if ([] === $rows) {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        } else {
            usort($rows, static function ($row1, $row2): int {
                return strcasecmp($row1[0], $row2[0]);
            });

            $this->io->table($headers, $rows);

            if ($this->apply) {
                $this->io->success('Labels successfully updated.');
            }
        }
    }
}
