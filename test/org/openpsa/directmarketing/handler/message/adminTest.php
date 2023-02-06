<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\directmarketing\handler\message;

use midcom_db_person;
use openpsa_testcase;
use openpsa_test_campaign_helper;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class adminTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    private static openpsa_test_campaign_helper $helper;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$helper = new openpsa_test_campaign_helper;
    }

    public function testHandler_edit()
    {
        $message = self::$helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'edit', $message->guid]);
        $this->assertEquals('message_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_copy()
    {
        $message = self::$helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'copy', $message->guid]);
        $this->assertEquals('message_copy', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $message = self::$helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'delete', $message->guid]);
        $this->assertEquals('message_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
