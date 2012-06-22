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
class midgard_admin_asgard_handler_object_rcsTest extends openpsa_testcase
{

    public function testHandler_history()
    {
        $this->create_user(true);
        $object_with_history = self::create_class_object('midcom_db_topic', array('_use_rcs' => true));
        $object_without_history = self::create_class_object('midcom_db_topic', array('_use_rcs' => false));

        midcom::get('auth')->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard', 'object', 'rcs', $object_with_history->guid));
        $this->assertEquals('____mfa-asgard-object_rcs_history', $data['handler_id']);

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard', 'object', 'rcs', $object_without_history->guid));
        $this->assertEquals('____mfa-asgard-object_rcs_history', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>