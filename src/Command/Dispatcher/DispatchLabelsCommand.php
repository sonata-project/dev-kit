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
use App\Config\LabelsConfiguration;
use App\Config\Projects;
use App\Domain\Value\Project;
use App\Github\Api\Labels;
use Github\Exception\ExceptionInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchLabelsCommand extends AbstractNeedApplyCommand
{
    private Projects $projects;
    private Labels $labels;

    public function __construct(Projects $projects, Labels $labels)
    {
        parent::__construct();

        $this->projects = $projects;
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

        $processor = new Processor();
        $config = $processor->processConfiguration(new LabelsConfiguration(), [
            'sonata' => Yaml::parseFile(__DIR__.'/../../../config/labels.yaml'),
        ]);

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->section($project->name());

                $this->updateLabels(
                    $project,
                    $config['labels']
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

    /**
     * @param array<string, array<string, string>> $labels
     */
    private function updateLabels(Project $project, array $labels): void
    {
        Assert::notEmpty($labels);

        $repository = $project->repository();

        $configuredLabels = $labels;
        $missingLabels = $labels;

        $headers = [
            'Name',
            'Actual color',
            'Needed color',
            'State',
        ];

        $rows = [];

        foreach ($this->labels->all($repository) as $label) {
            $name = $label->name();
            $color = $label->color();

            $shouldExist = \array_key_exists($name, $configuredLabels);
            $configuredColor = $shouldExist ? $configuredLabels[$name]['color'] : null;
            $shouldBeUpdated = $shouldExist && $color !== $configuredColor;

            if ($shouldExist) {
                unset($missingLabels[$name]);
            }

            $state = null;
            if (!$shouldExist) {
                $state = 'Deleted';
                if ($this->apply) {
                    $this->labels->remove($repository, $label);
                }
            } elseif ($shouldBeUpdated) {
                $state = 'Updated';
                if ($this->apply) {
                    $this->labels->update($repository, $label, [
                        'name' => $name,
                        'color' => $configuredColor,
                    ]);
                }
            }

            if ($state) {
                $rows[] = [
                    $name,
                    '#'.$color,
                    $configuredColor ? '#'.$configuredColor : 'N/A',
                    $state,
                ];
            }
        }

        foreach ($missingLabels as $name => $label) {
            $color = $label['color'];

            if ($this->apply) {
                $this->labels->create($repository, [
                    'name' => $name,
                    'color' => $color,
                ]);
            }

            $rows = [
                $name,
                'N/A',
                '#'.$color,
                'Created',
            ];
        }

        if (empty($rows)) {
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
