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
class org_openpsa_calendar_handler_adminTest extends openpsa_testcase
{
    public function testHandler_edit()
    {
        $this->create_user(true);
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $event = $this->create_object('org_openpsa_calendar_event_dba');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'edit', $event->guid));
        $this->assertEquals('event_edit', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_delete()
    {
        $this->create_user(true);
        midcom::get('auth')->request_sudo('org.openpsa.calendar');

        $event = $this->create_object('org_openpsa_calendar_event_dba');

        $data = $this->run_handler('org.openpsa.calendar', array('event', 'delete', $event->guid));
        $this->assertEquals('event_delete', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>