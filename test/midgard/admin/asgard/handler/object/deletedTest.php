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
class midgard_admin_asgard_handler_object_deletedTest extends openpsa_testcase
{
    public function testHandler_deleted()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard', 'object', 'deleted', 'dummy'));
        $this->assertEquals('____mfa-asgard-object_deleted', $data['handler_id']);
        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
