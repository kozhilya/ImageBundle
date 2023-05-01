<?php

namespace Kozhilya\ImageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kozhilya_image');

        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('path')->defaultValue('/upload')->end()
            ->arrayNode('rules')
//                    ->useAttributeAsKey('class')
            ->arrayPrototype()
            ->children()
            ->scalarNode('class')->cannotBeEmpty()->end()
            ->scalarNode('field')->cannotBeEmpty()->end()
            ->scalarNode('slug')->cannotBeEmpty()->end()
            ->booleanNode('translatable')->end()
            ->booleanNode('save_original')->defaultTrue()->end()
            ->booleanNode('use_slug')->defaultFalse()->end()
            ->booleanNode('use_voter')->defaultFalse()->end()
            ->arrayNode('files')
            ->arrayPrototype()
            ->children()
            ->scalarNode('format')->defaultValue('png')->end()
            ->scalarNode('width')->defaultNull()->end()
            ->scalarNode('height')->defaultNull()->end()
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