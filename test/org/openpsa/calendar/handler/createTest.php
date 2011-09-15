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
class org_openpsa_calendar_handler_createTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass()
    {
        self::$_user = self::create_user(true);
    }

    public function testHandler_new_event_for_person_with_time()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'new', self::$_user->guid, (string) time()));
        $this->assertEquals('new_event_for_person_with_time', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_new_event_for_person()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'new', self::$_user->guid));
        $this->assertEquals('new_event_for_person', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_new_event()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'new'));
        $this->assertEquals('new_event', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

}
?>