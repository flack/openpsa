<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_documents_directoryTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $user = $this->create_user(true);

        $_MIDCOM->auth->request_sudo('org.openpsa.documents');

        $directory = new org_openpsa_documents_directory();
        $directory->name = 'TEST_' . __CLASS__ . time();
        $stat = $directory->create();
        $this->assertTrue($stat);
        $this->register_object($directory);

        $stat = $directory->update();
        $this->assertTrue($stat);

        $stat = $directory->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }
}
?>