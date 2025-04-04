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

use App\Config\Projects;
use App\Domain\Value\Project;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

use function Symfony\Component\String\u;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DependsCommand extends AbstractCommand
{
    private Projects $projects;

    public function __construct(Projects $projects)
    {
        parent::__construct();

        $this->projects = $projects;
    }

    protected function configure(): void
    {
        $this
            ->setName('depends')
            ->setDescription('Show internal sonata dependencies of each project.')
            ->addOption('branch-depth', null, InputOption::VALUE_OPTIONAL, 'Number of branches to show.', '2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $branchDepth = (int) $input->getOption('branch-depth');
        Assert::greaterThan($branchDepth, 0, 'branch-depth needs to be greater than 0');

        foreach ($this->projects->all() as $project) {
            $this->io->title($project->name());

            $this->depends($project, $branchDepth);
        }

        return 0;
    }

    private function depends(Project $project, int $depth): void
    {
        $bd = 0;
        foreach ($project->package()->getVersions() as $version) {
            if (!u($version->getVersion())->endsWith('-dev')
                && !u($version->getVersion())->equalsTo('dev-master')
            ) {
                continue;
            }

            $this->io->writeln(\sprintf(
                '    <info>%s</info>',
                $version->getVersion()
            ));
            $this->io->newLine();

            foreach ($version->getRequire() as $packageName => $constraint) {
                if (!u($packageName)->startsWith('sonata-project/')) {
                    continue;
                }

                $this->io->writeln(\sprintf(
                    '        %s:%s',
                    $packageName,
                    $constraint
                ));
            }
            $this->io->newLine();

            if (++$bd >= $depth) {
                break;
            }
        }
    }
}
