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
use midcom_db_topic;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class moveTest extends openpsa_testcase
{
    public function testHandler_move()
    {
        midcom::get()->auth->request_sudo('midcom.admin.folder');
        $parent = $this->create_object(midcom_db_topic::class);
        $topic = $this->create_object(midcom_db_topic::class, ['up' => $parent->id]);
        $data = $this->run_handler('net.nehmer.static', ['__ais', 'folder', 'move', $topic->guid]);
        $this->assertEquals('move', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }
}
