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
class midgard_admin_user_handler_listTest extends openpsa_testcase
{
    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user']);
        $this->assertEquals('____mfa-asgard_midgard.admin.user-user_list', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_password_email()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'password', 'email']);
        $this->assertEquals('____mfa-asgard_midgard.admin.user-user_password_email', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_batch()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');
        $person = $this->create_user();
        $_POST = [
            'midgard_admin_user' => [$person->guid]
        ];
        $url = $this->run_relocate_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'batch', 'removeaccount']);
        $this->assertEquals('__mfa/asgard_midgard.admin.user/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_batch_groupadd()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');
        $person = $this->create_user();
        $_POST = [
            'midgard_admin_user' => [$person->guid]
        ];
        $url = $this->run_relocate_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'batch', 'groupadd']);
        $this->assertEquals('__mfa/asgard_midgard.admin.user/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_batch_passwords()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');
        $person = $this->create_user();
        $_POST = [
            'midgard_admin_user' => [$person->guid]
        ];
        $url = $this->run_relocate_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'batch', 'passwords']);
        $this->assertEquals('__mfa/asgard_midgard.admin.user/', $url);

        midcom::get()->auth->drop_sudo();
    }
}
