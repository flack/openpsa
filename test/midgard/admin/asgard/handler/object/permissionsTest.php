<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midgard_admin_asgard_handler_object_permissionsTest extends openpsa_testcase
{
    protected static $_object;

    public static function setUpBeforeClass() : void
    {
        self::$_object = self::create_class_object(midcom_db_topic::class, ['component' => 'org.openpsa.mypage']);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');
        self::$_object->set_privilege('midgard:read', 'EVERYONE', MIDCOM_PRIVILEGE_ALLOW);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_edit()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'permissions', self::$_object->guid]);
        $this->assertEquals('object_permissions', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }
}
