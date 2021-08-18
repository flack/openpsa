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
class editTest extends openpsa_testcase
{
    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('midcom.admin.folder');

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'folder', 'create']);
        $this->assertEquals('create', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('midcom.admin.folder');

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'folder', 'edit']);
        $this->assertEquals('edit', $data['handler_id']);

        $url = $this->submit_dm_dialog([
            'name' => uniqid(),
            'title' => 'test'
        ], 'net.nehmer.static', ['__ais', 'folder', 'edit']);
        $this->assertEquals('', $url);

        midcom::get()->auth->drop_sudo();
    }
}
