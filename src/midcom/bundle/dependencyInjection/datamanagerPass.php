<?php
namespace midcom\bundle\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;

class datamanagerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container) : void
    {
        $form_prefix = $this->get_prefix($container->getDefinition('form.factory'));

        $validator_builder = $container
            ->getDefinition('validator.builder')
            ->addMethodCall('addXmlMappings', [[$form_prefix . 'config/validation.xml']]);

        $container->getDefinition('translator')
            ->addArgument([
                $this->get_prefix($validator_builder, 'translations/validators.'),
                $form_prefix . 'translations/validators.'
            ]);
    }

    private function get_prefix(Definition $service, string $suffix = '') : string
    {
        $rc = new \ReflectionClass($service->getClass());
        return dirname($rc->getFileName()) . '/Resources/' . $suffix;
    }
}