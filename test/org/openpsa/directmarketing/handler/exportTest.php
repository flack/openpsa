<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\directmarketing\handler;

use midcom_db_person;
use openpsa_testcase;
use openpsa_test_campaign_helper;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class exportTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_csv()
    {
        $helper = new openpsa_test_campaign_helper;
        $campaign = $helper->get_campaign();
        $helper->get_member(self::$_person);

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'export', 'csv', $campaign->guid]);
        $this->assertEquals('export_csv', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
