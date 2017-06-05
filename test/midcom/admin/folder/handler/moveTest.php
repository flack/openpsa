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
class midcom_admin_folder_handler_moveTest extends openpsa_testcase
{
    public function testHandler_move()
    {
        midcom::get()->auth->request_sudo('midcom.admin.folder');
        $parent = $this->create_object('midcom_db_topic');
        $topic = $this->create_object('midcom_db_topic', ['up' => $parent->id]);
        $data = $this->run_handler('net.nehmer.static', ['__ais', 'folder', 'move', $topic->guid]);
        $this->assertEquals('____ais-folder-move', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
