<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\bundle\test;

use midcom_services_auth_acl;
use midcom_helper__componentloader;
use midcom_services_dbclassloader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use midcom\bundle\dependencyInjection\componentPass;
use midcom\events\watcher;
use PHPUnit\Framework\TestCase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class componentPassTest extends TestCase
{
    public function test_process()
    {
        $container = new ContainerBuilder();
        $container->register('auth.acl', midcom_services_auth_acl::class);
        $container->register('componentloader', midcom_helper__componentloader::class);
        $container->register('dbclassloader', midcom_services_dbclassloader::class);
        $container->register('watcher', watcher::class);
        $container->setParameter('midcom.midcom_components', [
            'midgard.admin.asgard' => dirname(MIDCOM_ROOT) . '/lib/midgard/admin/asgard'
        ]);
        $container->setParameter('midcom.person_class', \midgard_person::class);

        (new componentPass)->process($container);

        $found = false;
        foreach ($container->getDefinition('auth.acl')->getMethodCalls() as [$call, $args]) {
            if (   $call === 'register_default_privileges'
                && array_key_exists('midgard.admin.asgard:access', $args[0])) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Privileges were not registered');

        $watches = $container->getDefinition('watcher')->getArgument(0);
        $this->assertArrayHasKey(MIDCOM_OPERATION_DBA_DELETE, $watches);
        $this->assertNotEmpty($watches[MIDCOM_OPERATION_DBA_DELETE]);

        $components = $container->getDefinition('componentloader')->getArgument(0);
        $this->assertArrayHasKey('midgard.admin.asgard', $components);

        $map = $container->getDefinition('dbclassloader')->getArgument(0);
        $this->assertArrayHasKey('midgard.admin.asgard', $map);
        $this->assertArrayHasKey('midcom', $map);
        $this->assertArrayHasKey(\midgard_person::class, $map['midcom']);
    }
}
