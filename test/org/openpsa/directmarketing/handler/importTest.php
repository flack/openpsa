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
class org_openpsa_directmarketing_handler_importTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_index()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', $campaign->guid]);
        $this->assertEquals('import_main', $data['handler_id']);
        $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_simpleemails()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', 'simpleemails', $campaign->guid]);
        $this->assertEquals('import_simpleemails', $data['handler_id']);
        $this->show_handler($data);

        $email = __FUNCTION__ . '.' . time() . '@' . __CLASS__ . '.org';
        $_POST = [
            'org_openpsa_directmarketing_import_separator' => 'N',
            'org_openpsa_directmarketing_import_textarea' => $email,
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
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', 'csv', $campaign->guid]);
        $this->assertEquals('import_csv_file_select', $data['handler_id']);
        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_vcards()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'import', 'vcards', $campaign->guid]);
        $this->assertEquals('import_vcards', $data['handler_id']);
        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
