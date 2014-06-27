<?php

namespace RAPL\Bundle\RAPLBundle\Tests\DependencyInjection;

use RAPL\Bundle\RAPLBundle\DependencyInjection\RAPLExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class RAPLExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testDependencyInjectionConfiguration()
    {
        $container = $this->getContainer();
        $loader = new RAPLExtension();

        $loader->load(array(), $container);

        $this->assertEquals('RAPL\RAPL\Configuration', $container->getParameter('rapl.configuration.class'));
        $this->assertEquals('RAPL\RAPL\EntityManager', $container->getParameter('rapl.entity_manager.class'));
        $this->assertEquals('Proxies', $container->getParameter('rapl.proxy_namespace'));
        $this->assertEquals('%kernel.cache_dir%/rapl/Proxies', $container->getParameter('rapl.proxy_dir'));

        $config = array(
            'proxy_namespace' => 'MyProxies',
            'proxy_dir' => '/tmp/proxies'
        );
        $loader->load(array($config), $container);

        $this->assertEquals('MyProxies', $container->getParameter('rapl.proxy_namespace'));
        $this->assertEquals('/tmp/proxies', $container->getParameter('rapl.proxy_dir'));
    }

    private function getContainer()
    {
        $map = array();

        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug' => false,
            'kernel.bundles' => $map,
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__.'/../../' // src dir
        )));
    }
}
