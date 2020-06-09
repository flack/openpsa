<?php
namespace midcom\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use midcom_core_manifest;
use midcom_error;

class componentPass extends configPass
{
    /**
     * @var array
     */
    private $watches = [
        \MIDCOM_OPERATION_DBA_CREATE => [],
        \MIDCOM_OPERATION_DBA_UPDATE => [],
        \MIDCOM_OPERATION_DBA_DELETE => [],
        \MIDCOM_OPERATION_DBA_IMPORT => []
    ];

    public function process(ContainerBuilder $container)
    {
        $paths = [];
        foreach ($this->config->get('builtin_components', []) as $path) {
            $paths[] = dirname(MIDCOM_ROOT) . '/' . $path . '/config/manifest.inc';
        }

        // now we look for extra components the user may have registered
        foreach ($this->config->get('midcom_components', []) as $path) {
            if (!file_exists($path . '/config/manifest.inc')) {
                throw new midcom_error('No manifest found in path ' . $path);
            }
            $paths[] = $path . '/config/manifest.inc';
        }

        foreach ($paths as $path) {
            $manifest = new midcom_core_manifest($path);
            $components[$manifest->name] = $path;
            if ($manifest->watches !== null) {
                $this->add_watches($manifest->name, $manifest->watches);
            }

            $this->process_manifest($manifest, $container);
        }

        $cl = $container->getDefinition('componentloader');
        $cl->addArgument($components);

        $watcher = $container->getDefinition('watcher');
        $watcher->addArgument($this->watches);
    }

    private function add_watches(string $component, array $watches)
    {
        foreach ($watches as $watch) {
            foreach (array_keys($this->watches) as $operation_id) {
                // Check whether the operations flag list from the component
                // contains the operation_id we're checking a watch for.
                if ($watch['operations'] & $operation_id) {
                    $this->watches[$operation_id][] = [
                        $component => $watch['classes']
                    ];
                }
            }
        }
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
    }
}