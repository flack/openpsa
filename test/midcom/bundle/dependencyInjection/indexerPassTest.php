<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\bundle\test;

use midcom_config;
use midcom\bundle\dependencyInjection\indexerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use midcom_services_indexer;
use midcom_services_indexer_backend_solr;
use Symfony\Component\DependencyInjection\Reference;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class indexerPassTest extends TestCase
{
    public function test_process()
    {
        $config = new midcom_config;
        $config->set('indexer_backend', 'solr');
        $pass = new indexerPass($config);

        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects($this->exactly(3))
            ->method('getDefinition')
            ->with($this->logicalOr(
                $this->equalTo('indexer'),
                $this->equalTo('indexer.backend'),
                $this->equalTo('event_dispatcher')))
            ->will($this->returnCallback([$this, 'get_definition_mock']));

        $pass->process($container);
    }

    public function get_definition_mock($identifier)
    {
        $builder = $this->getMockBuilder(Definition::class);

        if ($identifier == 'event_dispatcher') {
            $dispatcher = $builder
                ->setConstructorArgs([EventDispatcher::class])
                ->getMock();
            $dispatcher
                ->expects($this->once())
                ->method('addMethodCall')
                ->with('addSubscriber', [new Reference('indexer')]);

            return $dispatcher;
        }
        if ($identifier == 'indexer.backend') {
            $backend = $builder
                ->setConstructorArgs([midcom_services_indexer_backend_solr::class])
                ->getMock();
            $backend
                ->expects($this->once())
                ->method('setClass')
                ->with(midcom_services_indexer_backend_solr::class);

            return $backend;
        }
        $indexer = $builder
            ->setConstructorArgs([midcom_services_indexer::class])
            ->getMock();
        $indexer
            ->expects($this->once())
            ->method('addArgument')
            ->with(new Reference('indexer.backend'));

        return $indexer;
    }
}
