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
use midcom_error;
use midcom_services_at_entry_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class sendTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    private static openpsa_test_campaign_helper $helper;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$helper = new openpsa_test_campaign_helper;
    }

    public function testHandler_send()
    {
        $message = self::$helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'send_test', $message->guid]);
        $this->assertEquals('test_send_message', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_send_bg()
    {
        $message = self::$helper->get_message();
        $at = $this->create_object(midcom_services_at_entry_dba::class);

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'send_bg', $message->guid, '2', $at->guid]);
        $this->assertEquals('background_send_message', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_send_bg_missing_message()
    {
        try {
            $this->run_handler('org.openpsa.directmarketing', ['message', 'send_bg', str_repeat('a', 32), '1', str_repeat('b', 32)]);
            $this->fail('Handler should have thrown for a missing message');
        } catch (midcom_error) {
        }

        $this->assertFalse(midcom::get()->auth->is_component_sudo());
    }

    public function testHandler_send_message()
    {
        $message = self::$helper->get_message();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['message', 'send', $message->guid]);
        $this->assertEquals('send_message', $data['handler_id']);

        // the handler schedules a background job, which must not outlive the test message
        $qb = midcom_services_at_entry_dba::new_query_builder();
        $qb->add_constraint('method', '=', 'background_send_message');
        $entries = array_filter($qb->execute(), fn ($entry) => str_contains($entry->arguments['url_base'] ?? '', $message->guid));
        $this->register_objects($entries);
        $this->assertCount(1, $entries);

        midcom::get()->auth->drop_sudo();
    }
}
