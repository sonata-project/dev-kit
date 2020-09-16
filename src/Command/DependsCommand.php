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

use Packagist\Api\Client as PackagistClient;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;
use function Symfony\Component\String\u;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class DependsCommand extends AbstractCommand
{
    private PackagistClient $packagist;

    public function __construct(PackagistClient $packagist)
    {
        parent::__construct();

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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $branchDepth = (int) $input->getOption('branch-depth');
        Assert::greaterThan($branchDepth, 0, 'branch-depth needs to be greater than 0');

        foreach ($this->configs['projects'] as $name => $config) {
            $package = $this->packagist->get(static::PACKAGIST_GROUP.'/'.$name);
            $this->io->title($package->getName());

            $bd = 0;
            foreach ($package->getVersions() as $version) {
                if ('-dev' !== substr($version->getVersion(), -4) && 'dev-master' !== $version->getVersion()) {
                    continue;
                }

                $this->io->writeln(sprintf(
                    '    <info>%s</info>',
                    $version->getVersion()
                ));
                $this->io->newLine();

                if (!\is_array($version->getRequire())) {
                    continue;
                }

                foreach ($version->getRequire() as $packageName => $constraint) {
                    if (!u($packageName)->startsWith('sonata-project/')) {
                        continue;
                    }

                    $this->io->writeln(sprintf(
                        '        %s',
                        $packageName.':'.$constraint
                    ));
                }
                $this->io->newLine();

                if (++$bd >= $branchDepth) {
                    break;
                }
            }
        }

        return 0;
    }
}
