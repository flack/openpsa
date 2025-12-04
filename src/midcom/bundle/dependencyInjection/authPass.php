<?php
namespace midcom\bundle\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class authPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        foreach (['frontend' => 'form', 'backend' => 'simple'] as $name => $default) {
            $class = $container->getParameter('midcom.auth_' . $name);
            if ($class && $class !== $default) {
                $definition = $container->getDefinition('auth.' . $name);
                $definition->setClass($class);
            }
        }
    }
}