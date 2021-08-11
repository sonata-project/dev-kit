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
            ->scalarNode('composer_version')->defaultValue('2')->end()
            ->booleanNode('panther')->defaultFalse()->end()
            ->booleanNode('phpstan')->defaultFalse()->end()
            ->booleanNode('psalm')->defaultFalse()->end()
            ->arrayNode('excluded_files')->prototype('scalar')->defaultValue([])->end()->end()
            ->scalarNode('custom_gitignore_part')->defaultNull()->end()
            ->scalarNode('custom_gitattributes_part')->defaultNull()->end()
            ->scalarNode('custom_doctor_rst_whitelist_part')->defaultNull()->end()
            ->booleanNode('has_documentation')->defaultTrue()->end()
            ->scalarNode('documentation_badge_slug')->defaultNull()->end()
            ->arrayNode('branches')
            ->normalizeKeys(false)
            ->defaultValue([])
            ->prototype('array')
            ->children()
            ->arrayNode('php')->prototype('scalar')->defaultValue([])->end()->end()
            ->arrayNode('tools')->prototype('scalar')->defaultValue([])->end()->end()
            ->arrayNode('php_extensions')->prototype('scalar')->defaultValue([])->end()->end()
            ->scalarNode('target_php')->defaultNull()->end()
            ->scalarNode('custom_gitignore_part')->defaultNull()->end()
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
