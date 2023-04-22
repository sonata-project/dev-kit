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
    /**
     * @psalm-suppress PossiblyNullReference, UndefinedInterfaceMethod
     *
     * @see https://github.com/psalm/psalm-plugin-symfony/issues/174
     */
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
                            ->booleanNode('has_documentation')->defaultTrue()->end()
                            ->booleanNode('has_test_kernel')->defaultTrue()->end()
                            ->booleanNode('has_platform_tests')->defaultFalse()->end()
                            ->scalarNode('documentation_badge_slug')->defaultNull()->end()
                            ->arrayNode('branches')
                                ->normalizeKeys(false)
                                ->defaultValue([])
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('php')->prototype('scalar')->defaultValue([])->end()->end()
                                        ->arrayNode('php_extensions')->prototype('scalar')->defaultValue([])->end()->end()
                                        ->scalarNode('target_php')->defaultNull()->end()
                                        ->scalarNode('frontend')->defaultFalse()->end()
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
            ->end();

        return $treeBuilder;
    }
}
