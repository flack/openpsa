<?php
namespace midcom\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class cachePass extends configPass
{
    public function process(ContainerBuilder $container)
    {
        foreach ($this->config->get('cache_autoload_queue') as $name) {
            $container->getDefinition('cache.module.' . $name)
                ->addMethodCall('initialize');

            $container->getDefinition('cache')
                ->addMethodCall('add_module', [$name, new Reference('cache.module.' . $name)]);
        }
    }
}