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
final class DispatchLabelsCommand extends AbstractNeedApplyCommand
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

                $this->updateLabels($project->package());
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateLabels(Package $package): void
    {
        $repositoryName = Util::getRepositoryNameWithoutVendorPrefix($package);

        $configuredLabels = $this->configs['labels'];
        $missingLabels = $configuredLabels;

        $headers = [
            'Name',
            'Actual color',
            'Needed color',
            'State',
        ];

        $rows = [];

        foreach ($this->github->repo()->labels()->all(static::GITHUB_GROUP, $repositoryName) as $label) {
            $name = $label['name'];
            $color = $label['color'];

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
                    $this->github->repo()->labels()->remove(static::GITHUB_GROUP, $repositoryName, $name);
                }
            } elseif ($shouldBeUpdated) {
                $state = 'Updated';
                if ($this->apply) {
                    $this->github->repo()->labels()->update(static::GITHUB_GROUP, $repositoryName, $name, [
                        'name' => $name,
                        'color' => $configuredColor,
                    ]);
                }
            }

            if ($state) {
                array_push($rows, [
                    $name,
                    '#'.$color,
                    $configuredColor ? '#'.$configuredColor : 'N/A',
                    $state,
                ]);
            }
        }

        foreach ($missingLabels as $name => $label) {
            $color = $label['color'];

            if ($this->apply) {
                $this->github->repo()->labels()->create(static::GITHUB_GROUP, $repositoryName, [
                    'name' => $name,
                    'color' => $color,
                ]);
            }
            array_push($rows, [$name, 'N/A', '#'.$color, 'Created']);
        }

        usort($rows, static function ($row1, $row2): int {
            return strcasecmp($row1[0], $row2[0]);
        });

        if (empty($rows)) {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        } else {
            $this->io->table($headers, $rows);

            if ($this->apply) {
                $this->io->success('Labels successfully updated.');
            }
        }
    }
}
