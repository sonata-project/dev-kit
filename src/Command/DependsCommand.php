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
use Packagist\Api\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Symfony\Component\String\u;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class DependsCommand extends Command
{
    private SymfonyStyle $io;
    private Projects $projects;
    private Client $packagist;

    public function __construct(Projects $projects, Client $packagist)
    {
        parent::__construct();

        $this->projects = $projects;
        $this->packagist = $packagist;
    }

    protected function configure(): void
    {
        $this
            ->setName('depends')
            ->setDescription('Show internal sonata dependencies of each project.')
            ->addOption('branch-depth', null, InputOption::VALUE_OPTIONAL, 'Number of branches to show.', 2)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branchDepth = (int) $input->getOption('branch-depth');

        /**
         * @var string $name
         * @var Project $project
         */
        foreach ($this->projects->all() as $name => $project) {
            $this->io->title($project->name());

            $package = $project->package();

            $bd = 0;
            foreach ($package->getVersions() as $version) {
                if ('-dev' !== substr($version->getVersion(), -4) && 'dev-master' !== $version->getVersion()) {
                    continue;
                }

                $this->io->section($version->getVersion());

                if (!\is_array($version->getRequire())) {
                    continue;
                }

                foreach ($version->getRequire() as $packageName => $constraint) {
                    if (!u($packageName)->startsWith('sonata-project/')) {
                        continue;
                    }

                    $this->io->writeln($packageName.':'.$constraint);
                }

                if (++$bd >= $branchDepth) {
                    break;
                }
            }
        }

        return 0;
    }
}
