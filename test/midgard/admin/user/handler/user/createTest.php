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
class midgard_admin_user_handler_user_createTest extends openpsa_testcase
{
    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'create']);
        $this->assertEquals('user_create', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
