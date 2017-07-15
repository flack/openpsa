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
class midgard_admin_asgard_handler_undeleteTest extends openpsa_testcase
{
    public function testHandler_trash()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'trash']);
        $this->assertEquals('____mfa-asgard-trash', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_trash_type()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'trash', 'midgard_style']);
        $this->assertEquals('____mfa-asgard-trash_type', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_trash_type_purge()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');
        $_POST = [
            'purge' => true,
            'undelete' => ['dummy']
        ];
        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'trash', 'midgard_style']);
        $this->assertEquals('____mfa-asgard-trash_type', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
