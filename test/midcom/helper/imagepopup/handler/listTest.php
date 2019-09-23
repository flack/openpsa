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
class midcom_helper_imagepopup_handler_listTest extends openpsa_testcase
{
    public function testHandler_list_folder()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $this->create_user(true);
        $node = self::get_component_node('net.nehmer.static');

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'imagepopup', 'folder', 'file', $node->guid]);
        $this->assertEquals('list_folder', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_folder_noobject()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $this->create_user(true);

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'imagepopup', 'folder', 'file']);
        $this->assertEquals('list_folder_noobject', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_unified()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $this->create_user(true);
        $node = self::get_component_node('net.nehmer.static');

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'imagepopup', 'unified', 'file', $node->guid]);
        $this->assertEquals('list_unified', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_unified_noobject()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $this->create_user(true);

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'imagepopup', 'unified', 'file']);
        $this->assertEquals('list_unified_noobject', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
