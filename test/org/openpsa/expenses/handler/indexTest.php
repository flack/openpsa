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
class org_openpsa_expenses_handler_indexTest extends openpsa_testcase
{
    public static function setUpBeforeClass()
    {
        self::create_user(true);
    }

    public function testHandler_index()
    {
        midcom::get('auth')->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses');
        $this->assertEquals('index', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_index_timestamp()
    {
        midcom::get('auth')->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', array('2011-01-26'));
        $this->assertEquals('index_timestamp', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>