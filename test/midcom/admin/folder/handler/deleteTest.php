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
class midcom_admin_folder_handler_deleteTest extends openpsa_testcase
{
    public function testHandler_delete()
    {
        midcom::get('auth')->request_sudo('midcom.admin.folder');

        $data = $this->run_handler('net.nehmer.static', array('__ais', 'folder', 'delete'));
        $this->assertEquals('____ais-folder-delete', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

}
?>