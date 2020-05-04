<?php
namespace midcom\dependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use midcom_config;

class configPass implements CompilerPassInterface
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
        $container->set('config', $this->config);
        $logger = $container->getDefinition('logger.filehandler');
        $logger->addArgument($this->config->get('log_filename'));
    }
}