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

use App\Github\Domain\Value\Label;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class ConfiguredLabels
{
    /**
     * @var array<string, Label>
     */
    private array $labels = [];

    public function __construct()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new LabelsConfiguration(), [
            'sonata' => Yaml::parseFile(__DIR__.'/../../config/labels.yaml'),
        ]);

        foreach ($config['labels'] as $name => $config) {
            $this->labels[$name] = Label::fromValues(
                $name,
                Label\Color::fromString($config['color'])
            );
        }
    }

    /**
     * @return array<string, Label>
     */
    public function all(): array
    {
        return $this->labels;
    }

    public function byName(string $name): Label
    {
        Assert::stringNotEmpty($name);
        Assert::keyExists(
            $this->labels,
            $name,
            sprintf(
                'Unknown label: %s',
                $name
            )
        );

        return $this->labels[$name];
    }

    public function shouldExist(Label $label): bool
    {
        return \array_key_exists($label->name(), $this->labels);
    }

    public function needsUpdate(Label $label): bool
    {
        if (!$this->shouldExist($label)) {
            return false;
        }

        $configured = $this->byName($label->name());

        return !$configured->equals($label);
    }
}
