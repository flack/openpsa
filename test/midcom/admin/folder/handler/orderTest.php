<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_admin_folder_handler_orderTest extends openpsa_testcase
{
    public function testHandler_order()
    {
        midcom::get('auth')->request_sudo('midcom.admin.folder');

        $data = $this->run_handler('net.nehmer.static', array('__ais', 'folder', 'order'));
        $this->assertEquals('____ais-folder-order', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

}
?>