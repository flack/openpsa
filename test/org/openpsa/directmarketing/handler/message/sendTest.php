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
class org_openpsa_directmarketing_handler_message_sendTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_send()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'send_test', $message->guid]);
        $this->assertEquals('test_send_message', $data['handler_id']);

        ob_start();
        $this->show_handler($data);
        ob_end_clean();
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_send_bg()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();
        $at = $this->create_object(midcom_services_at_entry_dba::class);

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'send_bg', $message->guid, '2', $at->guid]);
        $this->assertEquals('background_send_message', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
