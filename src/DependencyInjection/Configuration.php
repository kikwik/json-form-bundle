<?php

namespace Kikwik\JsonFormBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('kikwik_json_form');
        $rootNode = $treeBuilder->getRootNode();

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('model_map')
                    ->defaultValue([])
                    ->useAttributeAsKey('type')
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }

}