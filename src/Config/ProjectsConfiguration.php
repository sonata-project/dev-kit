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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class ProjectsConfiguration implements ConfigurationInterface
{
    private array $devKitConfigs;

    public function __construct(array $devKitConfigs)
    {
        $this->devKitConfigs = $devKitConfigs;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sonata');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('projects')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->normalizeKeys(false)
                    ->prototype('array')
                        ->children()
                            ->arrayNode('excluded_files')->prototype('scalar')->defaultValue([])->end()->end()
                            ->scalarNode('custom_gitignore_part')->defaultNull()->end()
                            ->scalarNode('custom_doctor_rst_whitelist_part')->defaultNull()->end()
                            ->booleanNode('docs_target')->defaultTrue()->end()
                            ->arrayNode('branches')
                                ->normalizeKeys(false)
                                ->defaultValue([])
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('php')->prototype('scalar')->defaultValue([])->end()->end()
                                        ->arrayNode('services')->prototype('scalar')->defaultValue([])->end()->end()
                                        ->scalarNode('target_php')->defaultNull()->end()
                                        ->arrayNode('variants')
                                            ->normalizeKeys(false)
                                            ->useAttributeAsKey('name')
                                            ->prototype('array')
                                                ->prototype('scalar')->end()
                                            ->end()
                                        ->end()
                                        ->scalarNode('docs_path')->defaultValue('docs')->end()
                                        ->scalarNode('tests_path')->defaultValue('tests')->end()
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
