<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\bundle\test;

use midcom_config;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use midcom_services_cache;
use Symfony\Component\DependencyInjection\Reference;
use midcom\bundle\dependencyInjection\cachePass;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Cache\VoidCache;
use Doctrine\Common\Cache\ApcuCache;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\SQLite3Cache;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class cachePassTest extends TestCase
{
    public function test_process_nap_apcu()
    {
        $config = new midcom_config;
        $config->set('cache_autoload_queue', ['nap']);
        $config->set('cache_module_memcache_backend', 'apc');
        $pass = new cachePass($config, OPENPSA2_UNITTEST_OUTPUT_DIR . '/cachetest');

        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->with($this->logicalOr('cache', 'cache.module.nap.backend'))
            ->will($this->returnCallback([$this, 'get_definition_mock']));

        $pass->process($container);
    }

    public function get_definition_mock($identifier)
    {
        if ($identifier == 'cache') {
            $cache = $this
                ->getMockBuilder(Definition::class)
                ->setConstructorArgs([midcom_services_cache::class])
                ->getMock();
            $cache
                ->expects($this->once())
                ->method('addMethodCall')
                ->with('add_module', ['nap', new Reference('cache.module.nap')]);

            return $cache;
        }
        $backend = $this
            ->getMockBuilder(Definition::class)
            ->setConstructorArgs([VoidCache::class])
            ->getMock();
        $backend
            ->expects($this->once())
            ->method('setClass')
            ->with(ApcuCache::class);

        return $backend;
    }

    public function test_process_content_memcached()
    {
        $container = $this->prepare_container();
        $container->register('cache.module.content.backend', VoidCache::class);
        $container->register('cache.module.content_data.backend', VoidCache::class);

        $config = new midcom_config;
        $config->set('cache_autoload_queue', ['content']);
        $config->set('cache_module_content_backend', ['driver' => 'memcached']);
        $pass = new cachePass($config, OPENPSA2_UNITTEST_OUTPUT_DIR . '/cachetest');

        $pass->process($container);

        $this->assertTrue($container->hasDefinition('cache.memcached.content'));
    }

    public function test_process_memcache_flatfile()
    {
        $container = $this->prepare_container();
        $backend = $container->register('cache.module.memcache.backend', VoidCache::class);

        $config = new midcom_config;
        $config->set('cache_autoload_queue', ['memcache']);
        $config->set('cache_module_memcache_backend', 'flatfile');
        $pass = new cachePass($config, OPENPSA2_UNITTEST_OUTPUT_DIR . '/cachetest');

        $pass->process($container);

        $this->assertEquals(FilesystemCache::class, $backend->getClass());
    }

    public function test_process_memcache_sqlite()
    {
        $container = $this->prepare_container();
        $backend = $container->register('cache.module.memcache.backend', VoidCache::class);

        $config = new midcom_config;
        $config->set('cache_autoload_queue', ['memcache']);
        $config->set('cache_module_memcache_backend', 'sqlite');
        $pass = new cachePass($config, OPENPSA2_UNITTEST_OUTPUT_DIR . '/cachetest');

        $pass->process($container);

        $this->assertEquals(SQLite3Cache::class, $backend->getClass());
    }

    private function prepare_container() : ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register('cache', midcom_services_cache::class);
        return $container;
    }
}
