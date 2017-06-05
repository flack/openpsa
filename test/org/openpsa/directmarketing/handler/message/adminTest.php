<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once OPENPSA_TEST_ROOT . 'org/openpsa/directmarketing/__helper/campaign.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_directmarketing_handler_message_adminTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_edit()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'edit', $message->guid]);
        $this->assertEquals('message_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_copy()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'copy', $message->guid]);
        $this->assertEquals('message_copy', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $message = $helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'delete', $message->guid]);
        $this->assertEquals('message_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
