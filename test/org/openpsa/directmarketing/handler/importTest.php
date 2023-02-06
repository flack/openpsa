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
use org_openpsa_contacts_person_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class importTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    private static openpsa_test_campaign_helper $helper;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$helper = new openpsa_test_campaign_helper;
    }

    public function testHandler_index()
    {
        $campaign = self::$helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', $campaign->guid]);
        $this->assertEquals('import_main', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_simpleemails()
    {
        $campaign = self::$helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', 'simpleemails', $campaign->guid]);
        $this->assertEquals('import_simpleemails', $data['handler_id']);

        $email = __FUNCTION__ . '.' . time() . '@openpsa2.org';
        $_POST = [
            'org_openpsa_directmarketing_import_separator' => 'N',
            'org_openpsa_directmarketing_import_textarea' => $email . "\n" . $email,
        ];
        $_FILES = [
            'org_openpsa_directmarketing_import_upload' => [
                'tmp_name' => null
            ]
        ];
        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', 'simpleemails', $campaign->guid]);
        $this->assertArrayHasKey('import_status', $data);
        $this->assertEquals(1, $data['import_status']['subscribed_new']);

        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->add_constraint('email', 'LIKE', $email);
        $results = $qb->execute();

        $this->register_objects($results);
        $this->assertCount(1, $results);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_csv_select()
    {
        $campaign = self::$helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', 'csv', $campaign->guid]);
        $this->assertEquals('import_csv_file_select', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_vcards()
    {
        $campaign = self::$helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', 'vcards', $campaign->guid]);
        $this->assertEquals('import_vcards', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }
}
