<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\bundle\test;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use midcom\bundle\dependencyInjection\authPass;
use PHPUnit\Framework\TestCase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class authPassTest extends TestCase
{
    public function test_process()
    {
        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects($this->exactly(2))
            ->method('getParameter')
            ->with($this->logicalOr(
                $this->equalTo('midcom.auth_backend'),
                $this->equalTo('midcom.auth_frontend')))
            ->willReturnCallback([$this, 'get_config']);

        $container
            ->expects($this->once())
            ->method('getDefinition')
            ->with('auth.frontend')
            ->willReturnCallback([$this, 'get_definition_mock']);

        (new authPass)->process($container);
    }

    public function get_config(string $key)
    {
        if ($key === 'midcom.auth_backend') {
            return null;
        }
        return 'test';
    }

    public function get_definition_mock()
    {
        $mock = $this->getMockBuilder(Definition::class)
            ->getMock();
        $mock->expects($this->once())
            ->method('setClass')
            ->with('test');
        return $mock;
    }
}
