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
use midcom_services_auth_acl;
use midcom_helper__componentloader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use midcom\dependencyInjection\componentPass;
use midcom\events\watcher;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class componentPassTest extends openpsa_testcase
{
    public function test_process()
    {
        $config = new midcom_config;
        $config->set('builtin_components', ['lib/net/nehmer/comments']);
        $config->set('midcom_components', [
            'midgard.admin.asgard' => dirname(MIDCOM_ROOT) . '/lib/midgard/admin/asgard'
        ]);
        $pass = new componentPass($config);

        $container = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects($this->exactly(3))
            ->method('getDefinition')
            ->with($this->logicalOr(
                $this->equalTo('auth.acl'),
                $this->equalTo('watcher'),
                $this->equalTo('componentloader')))
            ->will($this->returnCallback([$this, 'get_definition_mock']));

        $pass->process($container);
    }

    public function get_definition_mock(string $identifier)
    {
        $builder = $this->getMockBuilder(Definition::class);

        if ($identifier == 'watcher') {
            $dispatcher = $builder
                ->setConstructorArgs([watcher::class])
                ->getMock();
            $dispatcher
                ->expects($this->once())
                ->method('addArgument')
                ->with([
                    \MIDCOM_OPERATION_DBA_CREATE => [],
                    \MIDCOM_OPERATION_DBA_UPDATE => [],
                    \MIDCOM_OPERATION_DBA_DELETE => [[
                        'net.nehmer.comments' => []
                    ]],
                    \MIDCOM_OPERATION_DBA_IMPORT => []
                ]);

            return $dispatcher;
        }
        if ($identifier == 'auth.acl') {
            $acl = $builder
                ->setConstructorArgs([midcom_services_auth_acl::class])
                ->getMock();
            $acl
                ->expects($this->once())
                ->method('addMethodCall')
                ->with('register_default_privileges', [[
                    'midgard.admin.asgard:access' => MIDCOM_PRIVILEGE_DENY,
                    'midgard.admin.asgard:manage_objects' => MIDCOM_PRIVILEGE_ALLOW
                ]]);

            return $acl;
        }

        $componentloader = $builder
            ->setConstructorArgs([midcom_helper__componentloader::class])
            ->getMock();
        $componentloader
            ->expects($this->once())
            ->method('addArgument')
            ->with([
                'midgard.admin.asgard' => dirname(MIDCOM_ROOT) . '/lib/midgard/admin/asgard/config/manifest.inc',
                'net.nehmer.comments' => dirname(MIDCOM_ROOT) . '/lib/net/nehmer/comments/config/manifest.inc'
            ]);

        return $componentloader;
    }
}
