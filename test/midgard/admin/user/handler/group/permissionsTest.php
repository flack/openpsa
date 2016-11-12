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
class midgard_admin_user_handler_group_permissionsTest extends openpsa_testcase
{
    protected static $_group;

    public static function setupBeforeClass()
    {
        self::$_group = self::create_class_object('midcom_db_group');
    }

    public function testHandler_folders()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midgard.admin.user', 'group', 'folders', self::$_group->guid));
        $this->assertEquals('____mfa-asgard_midgard.admin.user-group_folders', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
