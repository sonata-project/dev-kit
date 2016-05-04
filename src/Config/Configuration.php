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
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sonata');

        $rootNode
            ->children()
                ->arrayNode('labels')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('color')->isRequired()->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('projects')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->normalizeKeys(false)
                    ->prototype('array')
                        ->children()
                            ->arrayNode('branches')
                                ->normalizeKeys(false)
                                ->defaultValue(array())
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('php_versions')->prototype('scalar')->defaultValue(array())->end()->end()
                                        ->arrayNode('symfony_versions')->prototype('scalar')->defaultValue(array())->end()->end()
                                        ->scalarNode('docs_path')->defaultValue('Resources/doc')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
