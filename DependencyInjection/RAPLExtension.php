<?php

namespace RAPL\Bundle\RAPLBundle\DependencyInjection;

use Symfony\Bridge\Doctrine\DependencyInjection\AbstractDoctrineExtension;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @author Piotr Minkina <projekty@piotrminkina.pl>
 * @author Nic Wortel <nd.wortel@gmail.com>
 * @package RAPL\Bundle\RAPLBundle\DependencyInjection
 */
class RAPLExtension extends AbstractDoctrineExtension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('rapl.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (empty($config['default_connection'])) {
            $keys = array_keys($config['connections']);
            $config['default_connection'] = reset($keys);
        }
        $container->setParameter('rapl.default_connection', $config['default_connection']);

        if (empty($config['default_manager'])) {
            $keys = array_keys($config['managers']);
            $config['default_manager'] = reset($keys);
        }
        $container->setParameter('rapl.default_manager', $config['default_manager']);

        // set some options as parameters and unset them
        $config = $this->overrideParameters($config, $container);

        // load the connections
        $this->loadConnections($config['connections'], $container);

        // load the managers
        $this->loadManagers(
            $config['managers'],
            $config['default_manager'],
            $container
        );
    }

    /**
     * @param array $options
     * @param ContainerBuilder $container
     * @return array
     */
    protected function overrideParameters(array $options, ContainerBuilder $container)
    {
        $overrides = array(
            'proxy_namespace',
            'proxy_dir',
            'auto_generate_proxy_classes',
        );

        foreach ($overrides as $key) {
            if (isset($options[$key])) {
                $container->setParameter('rapl.'.$key, $options[$key]);

                // the option should not be used, the parameter should be referenced
                unset($options[$key]);
            }
        }

        return $options;
    }

    /**
     * @param array $connections
     * @param ContainerBuilder $container
     */
    protected function loadConnections(
        array $connections,
        ContainerBuilder $container
    ) {
        $allConnections = array();

        foreach ($connections as $name => $connection) {
            $connectionType = isset($connection['type']) ? $connection['type'] : 'default';
            $connectionArgs = isset($connection['options']) ? $connection['options'] : array();
            $connectionDef = new Definition(
                sprintf('%%rapl.%s_connection.class%%', $connectionType),
                $connectionArgs
            );
            $id = sprintf('rapl.%s_connection', $name);
            $container->setDefinition($id, $connectionDef);
            $allConnections[$name] = $id;
        }
        $container->setParameter('rapl.connections', $allConnections);
    }

    /**
     * @param array $managers
     * @param string $defaultManager
     * @param ContainerBuilder $container
     */
    protected function loadManagers(
        array $managers,
        $defaultManager,
        ContainerBuilder $container)
    {
        $allManagers = array();

        foreach ($managers as $name => $manager) {
            $manager['name'] = $name;
            $id = sprintf('rapl.%s_manager', $name);
            $this->loadManager($manager, $defaultManager, $container);
            $allManagers[$name] = $id;
        }

        $container->setParameter('rapl.managers', $managers);
    }

    /**
     * @param array $manager
     * @param string $defaultManager
     * @param ContainerBuilder $container
     */
    protected function loadManager(
        array $manager,
        $defaultManager,
        ContainerBuilder $container
    ) {
        $configServiceId = sprintf(
            'rapl.%s_configuration',
            $manager['name']
        );
        $connectionName = isset($manager['connection']) ? $manager['connection'] : $manager['name'];

        if ($container->hasDefinition($configServiceId)) {
            $configurationDef = $container->getDefinition($configServiceId);
        } else {
            $configurationDef = new Definition('%rapl.configuration.class%');
            $container->setDefinition($configServiceId, $configurationDef);
        }

        $this->loadManagerBundlesMappingInformation($manager, $configurationDef, $container);
        
        $methods = array(
            'setMetadataDriver' => new Reference(sprintf('rapl.%s_metadata_driver', $manager['name'])),
            'setProxyDir' => '%rapl.proxy_dir%',
            'setProxyNamespace' => '%rapl.proxy_namespace%',
            'setAutoGenerateProxyClasses' => '%rapl.auto_generate_proxy_classes%',
        );


        foreach ($methods as $method => $arg) {
            if ($configurationDef->hasMethodCall($method)) {
                $configurationDef->removeMethodCall($method);
            }
            $configurationDef->addMethodCall($method, array($arg));
        }

        $managerArgs = array(
            new Reference(sprintf('rapl.%s_connection', $connectionName)),
            new Reference(sprintf('rapl.%s_configuration', $manager['name'])),
        );
        $managerDef = new Definition('%rapl.entity_manager.class%', $managerArgs);

        $container->setDefinition(sprintf('rapl.%s_entity_manager', $manager['name']), $managerDef);

        if ($manager['name'] == $defaultManager) {
            $container->setAlias(
                'rapl.entity_manager',
                new Alias(sprintf('rapl.%s_entity_manager', $manager['name']))
            );
        }
    }

    /**
     * @param array $manager
     * @param Definition $configurationDef
     * @param ContainerBuilder $container
     */
    protected function loadManagerBundlesMappingInformation(
        array $manager,
        Definition $configurationDef,
        ContainerBuilder $container
    ) {
        // reset state of drivers and alias map. They are only used by this methods and children.
        $this->drivers = array();
        $this->aliasMap = array();

        $this->loadMappingInformation($manager, $container);
        $this->registerMappingDrivers($manager, $container);

        if ($configurationDef->hasMethodCall('setEntityNamespaces')) {
            // TODO: Can we make a method out of it on Definition? replaceMethodArguments() or something.
            $calls = $configurationDef->getMethodCalls();
            foreach ($calls as $call) {
                if ($call[0] == 'setEntityNamespaces') {
                    $this->aliasMap = array_merge($call[1][0], $this->aliasMap);
                }
            }
            $method = $configurationDef->removeMethodCall('setEntityNamespaces');
        }
        $configurationDef->addMethodCall('setEntityNamespaces', array($this->aliasMap));
    }

    /**
     * @inheritdoc
     */
    protected function getObjectManagerElementName($name)
    {
        return 'rapl.' . $name;
    }

    /**
     * @inheritdoc
     */
    protected function getMappingObjectDefaultName()
    {
        return 'Resource';
    }

    /**
     * @inheritdoc
     */
    protected function getMappingResourceConfigDirectory()
    {
        return 'Resources/config/rapl';
    }

    /**
     * @inheritdoc
     */
    protected function getMappingResourceExtension()
    {
        return 'rapl';
    }
}
