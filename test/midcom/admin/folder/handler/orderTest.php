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
class midcom_admin_folder_handler_orderTest extends openpsa_testcase
{
    public function testHandler_order()
    {
        midcom::get()->auth->request_sudo('midcom.admin.folder');

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'folder', 'order']);
        $this->assertEquals('____ais-folder-order', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
