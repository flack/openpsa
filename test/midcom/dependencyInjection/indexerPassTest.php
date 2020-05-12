<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\test;

use openpsa_testcase;
use midcom_config;
use midcom\dependencyInjection\indexerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use midcom_services_indexer;
use midcom_services_indexer_backend_solr;
use midcom\events\dispatcher;
use Symfony\Component\DependencyInjection\Reference;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class indexerPassTest extends openpsa_testcase
{
    public function test_process()
    {
        $config = new midcom_config;
        $config->set('indexer_backend', 'solr');
        $pass = new indexerPass($config);

        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container
            ->expects($this->once())
            ->method('setDefinition')
            ->with('indexer.backend', new Definition(midcom_services_indexer_backend_solr::class));

        $container
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->with($this->logicalOr($this->equalTo('indexer'), $this->equalTo('event_dispatcher')))
            ->will($this->returnCallback([$this, 'get_definition_mock']));

        $pass->process($container);
    }

    public function get_definition_mock($identifier)
    {
        $builder = $this->getMockBuilder(Definition::class);

        if ($identifier == 'event_dispatcher') {
            $dispatcher = $builder
                ->setConstructorArgs([dispatcher::class])
                ->getMock();
            $dispatcher
                ->expects($this->once())
                ->method('addMethodCall')
                ->with('addSubscriber', [new Reference('indexer')]);

            return $dispatcher;
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
