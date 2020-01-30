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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
abstract class AbstractNeedApplyCommand extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $apply;

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Applies wanted requests');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->apply = $input->getOption('apply');
        if (!$this->apply) {
            $this->io->warning('This is a dry run execution. No change will be applied here.');
        }
    }
}
