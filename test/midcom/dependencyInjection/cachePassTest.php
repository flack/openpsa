<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use midcom_config;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use midcom_services_cache;
use midcom_services_cache_module_content;
use Symfony\Component\DependencyInjection\Reference;
use midcom\dependencyInjection\cachePass;
use PHPUnit\Framework\TestCase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class cachePassTest extends TestCase
{
    public function test_process()
    {
        $config = new midcom_config;
        $config->set('cache_autoload_queue', ['content']);
        $pass = new cachePass($config);

        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->with($this->logicalOr($this->equalTo('cache'), $this->equalTo('cache.module.content')))
            ->will($this->returnCallback([$this, 'get_definition_mock']));

        $pass->process($container);
    }

    public function get_definition_mock($identifier)
    {
        $builder = $this->getMockBuilder(Definition::class);

        if ($identifier === 'cache') {
            $cache = $builder
                ->setConstructorArgs([midcom_services_cache::class])
                ->getMock();
            $cache
                ->expects($this->once())
                ->method('addMethodCall')
                ->with('add_module', ['content', new Reference('cache.module.content')]);

            return $cache;
        }

        $content = $builder
            ->setConstructorArgs([midcom_services_cache_module_content::class])
            ->getMock();
        $content
            ->expects($this->once())
            ->method('addMethodCall')
            ->with('initialize');

        return $content;
    }
}
