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
class midcom_admin_user_handler_user_editTest extends openpsa_testcase
{
    protected static $_user;

    public static function setupBeforeClass()
    {
        self::$_user = self::create_user();
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('midcom.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midcom.admin.user', 'edit', self::$_user->guid));
        $this->assertEquals('____mfa-asgard_midcom.admin.user-user_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
