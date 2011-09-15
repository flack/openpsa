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
class org_openpsa_calendar_handler_viewTest extends openpsa_testcase
{
    public static function setUpBeforeClass()
    {
        self::create_user(true);
    }

    public function testHandler_month_view_with_date()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('month', '2012-10-17'));
        $this->assertEquals('month_view_with_date', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_month_view()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('month'));
        $this->assertEquals('month_view', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_week_view_with_date()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('week', '2012-10-17'));
        $this->assertEquals('week_view_with_date', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_week_view()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('week'));
        $this->assertEquals('week_view', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_day_view_with_date()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('day', '2012-10-17'));
        $this->assertEquals('day_view_with_date', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_day_view()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', array('day'));
        $this->assertEquals('day_view', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_view_raw()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');
        $event = $this->create_object('org_openpsa_calendar_event_dba');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'raw', $event->guid));
        $this->assertEquals('event_view_raw', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_view()
    {
        midcom::get('auth')->request_sudo('org.openpsa.calendar');
        $event = $this->create_object('org_openpsa_calendar_event_dba');

        $data = $this->run_handler('org.openpsa.calendar', array('event', $event->guid));
        $this->assertEquals('event_view', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

}
?>