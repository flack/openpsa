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
class midcom_services_rcs_backend_rcsTest extends openpsa_testcase
{
    protected static $_conf_backup;
    protected static $_config;

    public static function setUpBeforeClass()
    {
        self::$_conf_backup = $GLOBALS['midcom_config']['midcom_services_rcs_enable'];
        $GLOBALS['midcom_config']['midcom_services_rcs_enable'] = true;

        self::$_config = new midcom_services_rcs_config($GLOBALS['midcom_config']);
    }

    public function test_list_history()
    {
        $topic = $this->create_object('midcom_db_topic', array('_use_rcs' => false));
        $handler = self::$_config->get_handler($topic);

        $this->assertEquals(array(), $handler->list_history());
        $topic->_use_rcs = true;
        $topic->title = 'TEST';
        midcom::get('auth')->request_sudo('midcom.core');
        $this->assertTrue($topic->update(), midcom_connection::get_error_string());
        midcom::get('auth')->drop_sudo();
        $handler = self::$_config->get_handler($topic);

        $this->assertEquals(1, sizeof($handler->list_history()));
    }

    public static function tearDownAfterClass()
    {
        $GLOBALS['midcom_config']['midcom_services_rcs_enable'] = self::$_conf_backup;
    }
}
?>