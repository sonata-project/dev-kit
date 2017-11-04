<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DevKit\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class DependsCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('depends')
            ->setDescription('Show internal sonata dependencies of each project.')
            ->addOption('branch-depth', null, InputOption::VALUE_OPTIONAL, 'Number of branches to show.', 2);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $branchDepth = intval($input->getOption('branch-depth'));

        foreach ($this->configs['projects'] as $name => $config) {
            $package = $this->packagistClient->get(static::PACKAGIST_GROUP.'/'.$name);
            $this->io->title($package->getName());

            $bd = 0;
            foreach ($package->getVersions() as $version) {
                if ('-dev' !== substr($version->getVersion(), '-4') && 'dev-master' !== $version->getVersion()) {
                    continue;
                }
                $this->io->section($version->getVersion());

                if (!is_array($version->getRequire())) {
                    continue;
                }
                foreach ($version->getRequire() as $packageName => $constraint) {
                    if (!strstr($packageName, 'sonata-project/')) {
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
