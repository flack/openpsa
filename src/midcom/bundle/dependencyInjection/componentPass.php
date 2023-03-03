<?php
namespace midcom\bundle\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use midcom_core_manifest;
use midcom_error;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Finder\Finder;

class componentPass implements CompilerPassInterface
{
    private array $watches = [
        \MIDCOM_OPERATION_DBA_CREATE => [],
        \MIDCOM_OPERATION_DBA_UPDATE => [],
        \MIDCOM_OPERATION_DBA_DELETE => [],
        \MIDCOM_OPERATION_DBA_IMPORT => []
    ];

    private array $classmap = [];

    public function process(ContainerBuilder $container)
    {
        $paths = $this->find_builtin_components();

        // now we look for extra components the user may have registered
        foreach ($container->getParameter('midcom.midcom_components') as $path) {
            if (!file_exists($path . '/config/manifest.inc')) {
                throw new midcom_error('No manifest found in path ' . $path);
            }
            $paths[] = $path . '/config/manifest.inc';
        }

        $components = [];
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

        $dbclassloader = $container->getDefinition('dbclassloader');
        $dbclassloader->addArgument($this->classmap);
    }

    private function find_builtin_components() : array
    {
        $components = [];
        $finder = (new Finder())
            ->files()
            ->in([MIDCOM_ROOT, dirname(MIDCOM_ROOT) . '/src'])
            ->name('manifest.inc');
        foreach ($finder as $file) {
            $components[] = $file->getPathname();
        }
        return $components;
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
        $this->classmap[$manifest->name] = $manifest->class_mapping;
        if ($manifest->name == 'midcom') {
            $this->classmap['midcom'][$container->getParameter('midcom.person_class')] = \midcom_db_person::class;
        }
    }
}