<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DevKit\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class ProjectsConfiguration implements ConfigurationInterface
{
    /**
     * @var array
     */
    private $devKitConfigs;

    /**
     * @param array $devKitConfigs
     */
    public function __construct(array $devKitConfigs)
    {
        $this->devKitConfigs = $devKitConfigs;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sonata');

        $rootNode
            ->children()
                ->arrayNode('projects')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->normalizeKeys(false)
                    ->prototype('array')
                        ->children()
                            ->arrayNode('excluded_files')->prototype('scalar')->defaultValue([])->end()->end()
                            ->booleanNode('docs_target')->defaultTrue()->end()
                            ->arrayNode('branches')
                                ->normalizeKeys(false)
                                ->defaultValue([])
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('php')->prototype('scalar')->defaultValue([])->end()->end()
                                        ->arrayNode('services')->prototype('scalar')->defaultValue([])->end()->end()
                                        ->scalarNode('target_php')->defaultNull()->end()
                                        ->append($this->addVersionsNode())
                                        ->scalarNode('docs_path')->defaultValue('Resources/doc')->end()
                                        ->scalarNode('tests_path')->defaultValue('Tests')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function addVersionsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('versions');

        $childrenNode = $node->children();

        foreach ($this->devKitConfigs['packages'] as $key => $name) {
            $childrenNode->arrayNode($key)->prototype('scalar')->defaultValue([])->end()->end();
        }

        $childrenNode->end();

        return $node;
    }
}
