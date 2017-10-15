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
class org_openpsa_directmarketing_handler_campaign_rulesTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_edit_query()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign(org_openpsa_directmarketing_campaign_dba::TYPE_SMART);

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'edit_query', $campaign->guid]);
        $this->assertEquals('edit_campaign_query', $data['handler_id']);
        $this->show_handler($data);

        $_POST = $this->get_rules();

        $url = $this->run_relocate_handler('org.openpsa.directmarketing', ['campaign', 'edit_query', $campaign->guid]);
        $this->assertEquals('campaign/' . $campaign->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_query()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign(org_openpsa_directmarketing_campaign_dba::TYPE_SMART);

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $_POST = $this->get_rules();
        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'query', $campaign->guid]);
        $this->assertEquals('campaign_query', $data['handler_id']);
        $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }

    private function get_rules()
    {
        return [
            'midcom_helper_datamanager2_dummy_field_rules' => "Array
            (
               'type' => 'AND',
               'groups' => 'AND',
               'classes' => Array
               (
                   0 => Array
                   (
                       'type' => 'AND',
                       'groups' => 'AND',
                       'classes' => Array
                       (
                           3 => Array
                           (
                               'type' => 'AND',
                               'class' => 'org_openpsa_contacts_person_dba',
                               'rules' => Array
                               (
                                   0 => Array
                                   (
                                       'property' => 'email',
                                       'match' => 'LIKE',
                                       'value' => '%.test%',
                                   )
                               )
                           ),
                       ),
                   ),
               ),
           )",
            'midcom_helper_datamanager2_save' => true
        ];
    }
}
