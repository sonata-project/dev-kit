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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    public const GITHUB_GROUP = 'sonata-project';
    public const GITHUB_USER = 'SonataCI';
    public const GITHUB_EMAIL = 'thomas+ci@sonata-project.org';
    public const SONATA_CI_BOT = 'SonataCI';

    protected const LABEL_NOTHING_CHANGED = 'Nothing to be changed.';

    protected SymfonyStyle $io;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }
}
