<?php
namespace midcom\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class formPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('translator')
            ->addArgument([
                $this->get_prefix($container, 'validator.builder'),
                $this->get_prefix($container, 'form.factory')
            ]);
    }

    private static function get_prefix(ContainerBuilder $container, string $id) : string
    {
        $service = $container->getDefinition($id);
        $rc = new \ReflectionClass($service->getClass());
        return dirname($rc->getFileName()) . '/Resources/translations/validators.';
    }
}