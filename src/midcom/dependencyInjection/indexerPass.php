<?php
namespace midcom\dependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use midcom_config;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class indexerPass implements CompilerPassInterface
{
    /**
     * @var midcom_config
     */
    private $config;

    public function __construct(midcom_config $config)
    {
        $this->config = $config;
    }

    public function process(ContainerBuilder $container)
    {
        if ($class = $this->config->get('indexer_backend')) {
            if (strpos($class, '_') === false) {
                // Built-in backend called using the shorthand notation
                $class = "midcom_services_indexer_backend_" . $class;
            }

            $container->setDefinition('indexer.backend', new Definition($class));
            $backend = $container->getDefinition('indexer');
            $backend->addArgument(new Reference('indexer.backend'));

            $container->getDefinition('event_dispatcher')
                ->addMethodCall('addSubscriber', [new Reference('indexer')]);
        }
    }
}