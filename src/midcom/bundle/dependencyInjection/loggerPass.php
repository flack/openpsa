<?php
namespace midcom\bundle\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class loggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $filehandler = $container->getDefinition('logger.filehandler');
        $filehandler->addArgument($container->getParameter('midcom.log_filename'));
        $filehandler->addArgument(\midcom_debug::convert_level((int) $container->getParameter('midcom.log_level')));

        $logger = new ChildDefinition('logger');
        $logger->replaceArgument(0, 'request');
        $container->setDefinition('logger.controller_resolver', $logger);

        $resolver = $container->getDefinition('controller_resolver');
        $resolver->addArgument(new Reference('logger.controller_resolver'));
    }
}