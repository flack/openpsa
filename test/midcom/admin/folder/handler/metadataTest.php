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
class midcom_admin_folder_handler_metadataTest extends openpsa_testcase
{
    public function testHandler_metadata()
    {
        midcom::get('auth')->request_sudo('midcom.admin.folder');
        $node = self::get_component_node('net.nehmer.static');

        $data = $this->run_handler('net.nehmer.static', array('__ais', 'folder', 'metadata', $node->guid));
        $this->assertEquals('____ais-folder-metadata', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

}
?>