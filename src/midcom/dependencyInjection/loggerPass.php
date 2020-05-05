<?php
namespace midcom\dependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use midcom_config;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;

class loggerPass implements CompilerPassInterface
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
        $filehandler = $container->getDefinition('logger.filehandler');
        $filehandler->addArgument($this->config->get('log_filename'));
        $filehandler->addArgument(\midcom_debug::convert_level((int) $this->config->get('log_level')));

        $logger = new ChildDefinition('logger');
        $logger->replaceArgument(0, 'request');
        $container->setDefinition('logger.controller_resolver', $logger);

        $resolver = $container->getDefinition('controller_resolver');
        $resolver->addArgument(new Reference('logger.controller_resolver'));
    }
}