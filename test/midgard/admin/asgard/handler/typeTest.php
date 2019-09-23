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
class midgard_admin_asgard_handler_typeTest extends openpsa_testcase
{
    public function testHandler_type()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'midgard_snippetdir']);
        $this->assertEquals('type', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_type_search()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $_GET['search'] = 'dummy';

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'midgard_snippetdir']);
        $this->assertEquals('type', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }
}
