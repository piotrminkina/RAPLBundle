<?php

namespace RAPL\Bundle\RAPLBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rapl');

        $rootNode
            ->children()
                ->scalarNode('proxy_dir')->defaultValue('%kernel.cache_dir%/rapl/Proxies')->end()
                ->scalarNode('proxy_namespace')->defaultValue('Proxies')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
