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
class org_openpsa_directmarketing_handler_campaign_adminTest extends openpsa_testcase
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

        $data = $this->run_handler('org.openpsa.directmarketing', array('campaign', 'edit_query', $campaign->guid));
        $this->assertEquals('edit_campaign_query', $data['handler_id']);

        $_POST = array(
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
        );

        $url = $this->run_relocate_handler('org.openpsa.directmarketing', array('campaign', 'edit_query', $campaign->guid));
        $this->assertEquals('campaign/' . $campaign->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_edit()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('campaign', 'edit', $campaign->guid));
        $this->assertEquals('edit_campaign', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('campaign', 'delete', $campaign->guid));
        $this->assertEquals('delete_campaign', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
