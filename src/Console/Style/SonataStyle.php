<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DevKit\Console\Style;

use SebastianBergmann\Diff\Differ;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class SonataStyle extends SymfonyStyle
{
    /**
     * @var Differ
     */
    private $differ;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);

        $this->differ = new Differ();
    }

    /**
     * @param string|array $from
     * @param string|array $to
     */
    public function diff($from, $to)
    {
        $diffCount = 0;
        foreach ($this->differ->diffToArray($from, $to) as $diffLine) {
            if (0 !== $diffLine[1]) {
                ++$diffCount;
            }
        }
        if (0 === $diffCount) {
            return;
        }

        foreach (explode("\n", $this->differ->diff($from, $to)) as $line) {
            if (empty($line)) {
                $this->newLine();
                continue;
            }

            switch ($line[0]) {
                case '+':
                    $this->writeln(sprintf('<fg=green>%s</>', $line));
                    break;
                case '-':
                    $this->writeln(sprintf('<fg=red>%s</>', $line));
                    break;
                default:
                    $this->writeln($line);
            }
        }
    }
}
