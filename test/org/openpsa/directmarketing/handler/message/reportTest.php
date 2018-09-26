<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_directmarketing_handler_message_reportTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_report()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();
        $helper->get_log($message, self::$_person);
        $helper->get_receipt($message, self::$_person);

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'report', $message->guid]);
        $this->assertEquals('message_report', $data['handler_id']);
        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_status()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'send_status', $message->guid]);
        $this->assertEquals('message_send_status', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
