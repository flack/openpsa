<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\helper\imagepopup\handler;

use midcom_db_topic;
use openpsa_testcase;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class linksTest extends openpsa_testcase
{
    protected static midcom_db_topic $node;

    public static function setUpBeforeClass() : void
    {
        self::$node = self::get_component_node('net.nehmer.static');
    }

    public function testHandler_open()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');

        $url = $this->run_relocate_handler(self::$node, ['__ais', 'imagepopup', 'open', 'file', self::$node->guid]);
        $this->assertEquals('__ais/imagepopup/links/file/' . self::$node->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_open_noobject()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');

        $url = $this->run_relocate_handler(self::$node, ['__ais', 'imagepopup', 'open', 'dummy']);
        $this->assertEquals('__ais/imagepopup/folder/dummy/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_links()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $this->create_user(true);

        $data = $this->run_handler(self::$node, ['__ais', 'imagepopup', 'links', 'file', self::$node->guid]);
        $this->assertEquals('list_links', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_links_noobject()
    {
        midcom::get()->auth->request_sudo('midcom.helper.imagepopup');
        $this->create_user(true);

        $data = $this->run_handler(self::$node, ['__ais', 'imagepopup', 'links', 'dummy']);
        $this->assertEquals('list_links_noobject', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
