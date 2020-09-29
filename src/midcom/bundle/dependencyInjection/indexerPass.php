<?php
namespace midcom\bundle\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class indexerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($class = $container->getParameter('midcom.indexer_backend')) {
            if (!str_contains($class, '_')) {
                // Built-in backend called using the shorthand notation
                $class = "midcom_services_indexer_backend_" . $class;
            }

            $backend = $container->getDefinition('indexer.backend');
            $backend->setClass($class);
            $indexer = $container->getDefinition('indexer');
            $indexer->addArgument(new Reference('indexer.backend'));

            $container->getDefinition('event_dispatcher')
                ->addMethodCall('addSubscriber', [new Reference('indexer')]);
        }
    }
}