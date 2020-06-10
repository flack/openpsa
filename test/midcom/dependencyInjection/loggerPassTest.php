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
use Symfony\Component\DependencyInjection\Reference;
use midcom\dependencyInjection\loggerPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class loggerPassTest extends TestCase
{
    public function test_process()
    {
        $config = new midcom_config;
        $config->set('log_filename', 'testname');
        $config->set('log_level', MIDCOM_LOG_DEBUG);
        $pass = new loggerPass($config);

        $logger = new ChildDefinition('logger');
        $logger->replaceArgument(0, 'request');

        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects($this->once())
            ->method('setDefinition')
            ->with('logger.controller_resolver', $logger);

        $container
            ->expects($this->exactly(2))
            ->method('getDefinition')
            ->with($this->logicalOr(
                $this->equalTo('logger.filehandler'),
                $this->equalTo('controller_resolver')))
            ->will($this->returnCallback([$this, 'get_definition_mock']));

        $pass->process($container);
    }

    public function get_definition_mock($identifier)
    {
        $builder = $this->getMockBuilder(Definition::class);

        if ($identifier == 'controller_resolver') {
            $resolver = $builder
                ->setConstructorArgs([ControllerResolver::class])
                ->getMock();
            $resolver
                ->expects($this->once())
                ->method('addArgument')
                ->with(new Reference('logger.controller_resolver'));

            return $resolver;
        }
        $filehandler = $builder
            ->setConstructorArgs([StreamHandler::class])
            ->getMock();
        $filehandler
            ->expects($this->exactly(2))
            ->method('addArgument')
            ->with($this->logicalOr(
                $this->equalTo('testname'),
                $this->equalTo(Logger::DEBUG)));

        return $filehandler;
    }
}
