<?php
namespace midcom\bundle\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;

class loggerPass extends configPass
{
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