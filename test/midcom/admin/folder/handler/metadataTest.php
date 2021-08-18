<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\admin\folder\handler;

use openpsa_testcase;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class metadataTest extends openpsa_testcase
{
    public function testHandler_metadata()
    {
        midcom::get()->auth->request_sudo('midcom.admin.folder');
        $node = self::get_component_node('net.nehmer.static');

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'folder', 'metadata', $node->guid]);
        $this->assertEquals('metadata', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
