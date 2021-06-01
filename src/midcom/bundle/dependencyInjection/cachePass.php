<?php
namespace midcom\bundle\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Memcached;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;

class cachePass implements CompilerPassInterface
{
    const NS_PLACEHOLDER = '__NAMESPACE__';

    public static function factory(string $name, string $classname, ...$args) : AdapterInterface
    {
        foreach ($args as &$arg) {
            if ($arg === self::NS_PLACEHOLDER) {
                $arg = $name . $_SERVER['SERVER_NAME'];
            }
        }
        return new $classname(...$args);
    }

    public function process(ContainerBuilder $container)
    {
        foreach ($container->getParameter('midcom.cache_autoload_queue') as $name) {
            $container->getDefinition('cache')
                ->addMethodCall('add_module', [$name, new Reference('cache.module.' . $name)]);

            if (in_array($name, ['nap', 'memcache'])) {
                if ($driver = $container->getParameter('midcom.cache_module_memcache_backend')) {
                    $config = $container->getParameter('midcom.cache_module_memcache_backend_config');
                    $this->configure_backend($name, $driver, $config, $container);
                }
            } else {
                $config = $container->getParameter('midcom.cache_module_content_backend');
                if (!empty($config['driver'])) {
                    if (!isset($config['directory'])) {
                        $config['directory'] = 'content/';
                    }

                    $this->configure_backend('content', $config['driver'], $config, $container);
                    $this->configure_backend('content_data', $config['driver'], $config, $container);
                }
            }
        }
    }

    private function configure_backend(string $name, string $driver, array $config, ContainerBuilder $container)
    {
        $backend = $container->getDefinition('cache.module.' . $name . '.backend');
        $directory = $container->getParameter('kernel.cache_dir');

        if (!empty($config['directory'])) {
            $directory .= '/' . $config['directory'];
        }

        switch ($driver) {
            case 'apc':
                $backend->setArguments([$name, ApcuAdapter::class, self::NS_PLACEHOLDER]);
                break;
            case 'memcached':
                if ($memcached = self::prepare_memcached($config)) {

                    $definition = $container->register('cache.memcached.' . $name, \Memcached::class);
                    $server = $memcached->getServerList()[0];
                    $definition->addMethodCall('addServer', [$server['host'], $server['port']]);

                    $backend->setArguments([$name, MemcachedAdapter::class, $definition, self::NS_PLACEHOLDER]);
                    break;
                }
                // fall-through
            case 'dba':
            case 'flatfile':
                $backend->setArguments([$name, FilesystemAdapter::class, self::NS_PLACEHOLDER, 0, $directory . '/' . $name]);
                break;
            case 'sqlite':
                $backend->setArguments([$name, PdoAdapter::class, "{$directory}/sqlite.db", self::NS_PLACEHOLDER]);
                break;
            default:
                $backend->setArguments([$name, NullAdapter::class]);
        }
    }

    public static function prepare_memcached(array $config) : ?Memcached
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? 11211;
        $memcached = new Memcached;
        if (!$memcached->addServer($host, $port)) {
            return null;
        }

        return $memcached;
    }
}