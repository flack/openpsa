<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\directmarketing\sender\backend;

use openpsa_testcase;
use org_openpsa_directmarketing_campaign_dba;
use midcom_db_topic;
use midcom\datamanager\datamanager;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class emailTest extends openpsa_testcase
{
    public function test_send()
    {
        $topic = $this->create_object(midcom_db_topic::class, ['component' => 'org.openpsa.directmarketing']);
        $campaign = $this->create_object(org_openpsa_directmarketing_campaign_dba::class, [
            'node' => $topic->id
        ]);
        $message = $this->create_object(\org_openpsa_directmarketing_campaign_message_dba::class, [
            'campaign' => $campaign->id
        ]);

        $datamanager = datamanager::from_schemadb('file:/org/openpsa/directmarketing/config/schemadb_default_message.inc')
            ->set_storage($message);

        $config = [
            'dm_storage' => $datamanager->get_storage()
        ];
        $sender = new \org_openpsa_directmarketing_sender_backend_email($config, $message);

        $person = new \org_openpsa_contacts_person_dba;
        $person->email = 'test@openpsa2.org';

        $member = new \org_openpsa_directmarketing_campaign_member_dba;
        $member->person = $person->id;
        $member->campaign = $campaign->id;

        $sender->send($person, $member, 'test', 'test', 'test', 'from@openpsa2.org');

        $this->assertCount(1, \org_openpsa_mail_backend_unittest::$mails);
    }
}
