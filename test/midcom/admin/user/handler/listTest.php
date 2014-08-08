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
class midcom_admin_user_handler_listTest extends openpsa_testcase
{
    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('midcom.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midcom.admin.user'));
        $this->assertEquals('____mfa-asgard_midcom.admin.user-user_list', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_password_email()
    {
        midcom::get()->auth->request_sudo('midcom.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midcom.admin.user', 'password', 'email'));
        $this->assertEquals('____mfa-asgard_midcom.admin.user-user_password_email', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
