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
class midgard_admin_user_handler_user_accountTest extends openpsa_testcase
{
    protected static $_user;

    public static function setupBeforeClass()
    {
        self::$_user = self::create_user();
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midgard.admin.user', 'account', self::$_user->guid));
        $this->assertEquals('____mfa-asgard_midgard.admin.user-user_edit_account', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midgard.admin.user', 'account', 'delete', self::$_user->guid));
        $this->assertEquals('____mfa-asgard_midgard.admin.user-user_delete_account', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_passwords()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midgard.admin.user', 'password'));
        $this->assertEquals('____mfa-asgard_midgard.admin.user-user_passwords', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
