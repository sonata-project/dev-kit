<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DevKit\Console;

use Sonata\DevKit\Console\Command\DispatchCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class Application extends BaseApplication
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->add(new DispatchCommand());
    }
}
