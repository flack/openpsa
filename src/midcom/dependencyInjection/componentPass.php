<?php
namespace midcom\dependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use midcom_config;
use midcom_core_manifest;
use midcom_error;

class componentPass implements CompilerPassInterface
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
        $components = [];
        foreach ($this->config->get('builtin_components', []) as $path) {
            $path = dirname(MIDCOM_ROOT) . '/' . $path . '/config/manifest.inc';
            $manifest = new midcom_core_manifest($path);
            $components[$manifest->name] = $path;
            $this->process_manifest($manifest, $container);
        }

        // now we look for extra components the user may have registered
        foreach ($this->config->get('midcom_components', []) as $path) {
            if (!file_exists($path . '/config/manifest.inc')) {
                throw new midcom_error('No manifest found in path ' . $path);
            }
            $path .= '/config/manifest.inc';
            $manifest = new midcom_core_manifest($path);
            $components[$manifest->name] = $path;
            $this->process_manifest($manifest, $container);
        }
        $cl = $container->getDefinition('componentloader');
        $cl->addArgument($components);
    }

    /**
     * Register manifest data.
     *
     * All default privileges are made known to ACL, the watches are registered
     */
    private function process_manifest(midcom_core_manifest $manifest, ContainerBuilder $container)
    {
        // Register Privileges
        if ($manifest->privileges) {
            $acl = $container->getDefinition('auth.acl');
            $acl->addMethodCall('register_default_privileges', [$manifest->privileges]);
        }
        // Register watches
        if ($manifest->watches !== null) {
            $dispatcher = $container->getDefinition('event_dispatcher');
            $dispatcher->addMethodCall('add_watches', [$manifest->watches, $manifest->name]);
        }
    }
}