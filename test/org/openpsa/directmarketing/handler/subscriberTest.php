<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\directmarketing\handler;

use openpsa_testcase;
use openpsa_test_campaign_helper;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class subscriberTest extends openpsa_testcase
{
    protected static $_person;

    /**
     * @var openpsa_test_campaign_helper
     */
    private static $helper;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$helper = new openpsa_test_campaign_helper;
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'list', self::$_person->guid]);
        $this->assertEquals('list_campaign_person', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_unsubscribe()
    {
        $member = self::$helper->get_member(self::$_person);
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'unsubscribe', $member->guid]);
        $this->assertEquals('subscriber_unsubscribe', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_unsubscribe_all()
    {
        self::$helper->get_member(self::$_person);
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'unsubscribe_all', self::$_person->guid]);
        $this->assertEquals('subscriber_unsubscribe_all', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_unsubscribe_all_future()
    {
        self::$helper->get_member(self::$_person);
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'unsubscribe_all_future', self::$_person->guid, 'test']);
        $this->assertEquals('subscriber_unsubscribe_all_future', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_unsubscribe_ajax()
    {
        $member = self::$helper->get_member(self::$_person);
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'unsubscribe', 'ajax', $member->guid]);
        $this->assertEquals('subscriber_unsubscribe_ajax', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
