<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_documents_handler_directory_viewTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_directory;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        $siteconf = org_openpsa_core_siteconfig::get_instance();
        self::$_directory = new midcom_db_topic($siteconf->get_node_guid('org.openpsa.documents'));
    }

    public function testHandler_view()
    {
        midcom::get('auth')->request_sudo('org.openpsa.documents');

        $data = $this->run_handler('org.openpsa.documents', array());
        $this->assertEquals('directory-view', $data['handler_id']);

        $data = $this->run_handler('org.openpsa.documents', array('directory', 'xml', self::$_directory->guid));
        $this->assertEquals('directory-single-view', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>