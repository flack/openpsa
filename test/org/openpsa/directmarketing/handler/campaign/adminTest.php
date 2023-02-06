<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\directmarketing\handler\campaign;

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
        $campaign = self::$helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'edit', $campaign->guid]);
        $this->assertEquals('edit_campaign', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $campaign = self::$helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'delete', $campaign->guid]);
        $this->assertEquals('delete_campaign', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
