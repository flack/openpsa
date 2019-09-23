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
class midcom_helper_imagepopup_handler_linksTest extends openpsa_testcase
{
    public function testHandler_open()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $node = self::get_component_node('net.nehmer.static');

        $url = $this->run_relocate_handler('net.nehmer.static', ['__ais', 'imagepopup', 'open', 'file', $node->guid]);
        $this->assertEquals('__ais/imagepopup/links/file/' . $node->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_open_noobject()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');

        $url = $this->run_relocate_handler('net.nehmer.static', ['__ais', 'imagepopup', 'open', 'dummy']);
        $this->assertEquals('__ais/imagepopup/folder/dummy/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_links()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $this->create_user(true);
        $node = self::get_component_node('net.nehmer.static');

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'imagepopup', 'links', 'file', $node->guid]);
        $this->assertEquals('list_links', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_links_noobject()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $this->create_user(true);

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'imagepopup', 'links', 'dummy']);
        $this->assertEquals('list_links_noobject', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
