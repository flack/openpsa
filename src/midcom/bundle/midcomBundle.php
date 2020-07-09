<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\DependencyInjection\FormPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use midcom\bundle\dependencyInjection\translatorPass;
use midcom\bundle\dependencyInjection\loggerPass;
use midcom\bundle\dependencyInjection\componentPass;
use midcom\bundle\dependencyInjection\cachePass;
use midcom\bundle\dependencyInjection\indexerPass;
use midcom_config;

class midcomBundle extends Bundle
{
    /**
     * @var midcom_config
     */
    private $config;

    public function __construct(midcom_config $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');
        $loader->load('form.yml');
        $container->addCompilerPass(new loggerPass($this->config));
        $container->addCompilerPass(new componentPass($this->config));
        $container->addCompilerPass(new cachePass($this->config, $container->getParameter('kernel.cache_dir')));
        $container->addCompilerPass(new indexerPass($this->config));
        $container->addCompilerPass(new FormPass);
        $container->addCompilerPass(new translatorPass);
    }
}
