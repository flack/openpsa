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
use org_openpsa_directmarketing_campaign_dba;
use org_openpsa_contacts_person_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class rulesTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    private static openpsa_test_campaign_helper $helper;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$helper = new openpsa_test_campaign_helper;
    }

    public function testHandler_edit_query()
    {
        $campaign = self::$helper->get_campaign(org_openpsa_directmarketing_campaign_dba::TYPE_SMART);

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'edit_query', $campaign->guid]);
        $this->assertEquals('edit_campaign_query', $data['handler_id']);

        $_POST = $this->get_rules();

        $url = $this->run_relocate_handler('org.openpsa.directmarketing', ['campaign', 'edit_query', $campaign->guid]);
        $this->assertEquals('campaign/' . $campaign->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_query()
    {
        $campaign = self::$helper->get_campaign(org_openpsa_directmarketing_campaign_dba::TYPE_SMART);

        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $_POST = $this->get_rules();
        $data = $this->run_handler('org.openpsa.directmarketing', ['campaign', 'query', $campaign->guid]);
        $this->assertEquals('campaign_query', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    private function get_rules()
    {
        return [
            'midcom_helper_datamanager2_dummy_field_rules' => json_encode([
                'type' => 'AND',
                'groups' => 'AND',
                'classes' => [
                    0 => [
                        'type' => 'AND',
                        'groups' => 'AND',
                        'classes' => [
                            3 => [
                                'type' => 'AND',
                                'class' => org_openpsa_contacts_person_dba::class,
                                'rules' => [
                                    0 => [
                                        'property' => 'email',
                                        'match' => 'LIKE',
                                        'value' => '%.test%',
                                    ]
                                ]
                            ],
                        ],
                    ],
                ],
            ]),
            'midcom_helper_datamanager2_save' => true
        ];
    }
}
