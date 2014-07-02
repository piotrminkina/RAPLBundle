<?php

namespace RAPL\Bundle\RAPLBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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

        $this->addManagersSection($rootNode);
        $this->addConnectionsSection($rootNode);

        $rootNode
            ->children()
                ->scalarNode('proxy_dir')
                    ->defaultValue('%kernel.cache_dir%/rapl/Proxies')
                ->end()
                ->booleanNode('auto_generate_proxy_classes')
                    ->defaultValue('%kernel.debug%')
                ->end()
                ->scalarNode('proxy_namespace')
                    ->defaultValue('Proxies')
                ->end()
                ->scalarNode('metadata_driver')
                    ->defaultValue('RAPL\RAPL\Mapping\Driver\YamlDriver')
                ->end()
                ->scalarNode('repository_factory')
                    ->defaultValue('RAPL\RAPL\Repository\DefaultRepositoryFactory')
                ->end()
                ->scalarNode('default_connection')->end()
                ->scalarNode('default_manager')->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    protected function addManagersSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('managers')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->treatNullLike(array())
                        ->children()
                            ->scalarNode('connection')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param ArrayNodeDefinition $rootNode
     */
    protected function addConnectionsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('type')->end()
                            ->arrayNode('options')
                                ->treatNullLike(array())
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}
