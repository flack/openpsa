<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\bundle;

use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\DependencyInjection\FormPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use midcom\bundle\dependencyInjection\datamanagerPass;
use midcom\bundle\dependencyInjection\loggerPass;
use midcom\bundle\dependencyInjection\componentPass;
use midcom\bundle\dependencyInjection\cachePass;
use midcom\bundle\dependencyInjection\indexerPass;
use midcom\bundle\dependencyInjection\authPass;

class midcomBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');
        $loader->load('form.yml');
        $loader->load('commands.yml');
        $container->addCompilerPass(new authPass);
        $container->addCompilerPass(new loggerPass);
        $container->addCompilerPass(new componentPass);
        $container->addCompilerPass(new cachePass);
        $container->addCompilerPass(new indexerPass);
        $container->addCompilerPass(new FormPass);
        $container->addCompilerPass(new datamanagerPass);
        $container->addCompilerPass(new AddConsoleCommandPass);
    }
}
