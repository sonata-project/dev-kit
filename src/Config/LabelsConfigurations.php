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

namespace App\Config;

use App\Domain\Value\Project;
use App\Github\Domain\Value\Label;
use Packagist\Api\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class LabelsConfigurations
{
    /**
     * @var array<string, Label>
     */
    private array $labels = [];

    public function __construct()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new DevKitConfiguration(), [
            'sonata' => Yaml::parse(file_get_contents(__DIR__.'/../../config/dev-kit.yaml')),
        ]);

        foreach ($config['labels'] as $label) {
            $name = $label['name'];

            $this->labels[$name] = Label::fromValues($name, $label['color']);
        }
    }

    /**
     * @return array<string, Project>
     */
    public function all(): array
    {
        return $this->labels;
    }

    public function shouldBeAvailable(Label $label): Project
    {
        return \array_key_exists($label->toString(), $this->labels);
    }
}
