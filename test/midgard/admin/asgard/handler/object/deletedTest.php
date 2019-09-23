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
        $obj = $this->create_object(midcom_db_topic::class);
        midcom::get()->auth->admin = true;
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $url = $this->run_relocate_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'deleted', $obj->guid]);
        $this->assertEquals('__mfa/asgard/object/open/' . $obj->guid . '/', $url);
        $obj->delete();

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'deleted', $obj->guid]);
        $this->assertEquals('object_deleted', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }
}
