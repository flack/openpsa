<?php
namespace midcom\bundle\dependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Doctrine\Common\Cache;
use SQLite3;
use midcom_services_cache_module_memcache;
use midcom_config;

class cachePass extends configPass
{
    private $cachedir;

    public function __construct(midcom_config $config, string $cachedir)
    {
        parent::__construct($config);
        $this->cachedir = $cachedir;
    }

    public function process(ContainerBuilder $container)
    {
        foreach ($this->config->get('cache_autoload_queue') as $name) {
            $container->getDefinition('cache')
                ->addMethodCall('add_module', [$name, new Reference('cache.module.' . $name)]);

            if ($name == 'nap' || $name == 'memcache') {
                if ($driver = $this->config->get('cache_module_memcache_backend')) {
                    $config = $this->config->get('cache_module_memcache_backend_config');
                    $this->configure_backend($name, $driver, $config, $container);
                }
            } else {
                $config = $this->config->get('cache_module_content_backend');
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

        $directory = $this->cachedir;
        if (!empty($config['directory'])) {
            $directory .= '/' . $config['directory'];
        }

        switch ($driver) {
            case 'apc':
                $backend->setClass(Cache\ApcuCache::class);
                break;
            case 'memcached':
                if ($memcached = midcom_services_cache_module_memcache::prepare_memcached($config)) {

                    $definition = $container->register('cache.memcached.' . $name, \Memcached::class);
                    $server = $memcached->getServerList()[0];
                    $definition->addMethodCall('addServer', [$server['host'], $server['port']]);

                    $backend->setClass(Cache\MemcachedCache::class);
                    $backend->addMethodCall('setMemcached', [$definition]);
                    break;
                }
                // fall-through
            case 'dba':
            case 'flatfile':
                $backend->setClass(Cache\FilesystemCache::class);
                $backend->addArgument($directory . '/' . $name);
                break;
            case 'sqlite':
                $definition = $container->register('cache.sqlite.' . $name, \SQLite3::class);
                $definition->setArguments(["{$directory}/sqlite.db"]);

                $backend->setClass(Cache\SQLite3Cache::class);
                $backend->setArguments([$definition, $name]);
                break;
        }
    }

}