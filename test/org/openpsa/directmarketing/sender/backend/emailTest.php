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
use org_openpsa_directmarketing_campaign_message_dba;
use midcom_db_topic;
use midcom\datamanager\datamanager;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class emailTest extends openpsa_testcase
{
    private static $campaign;

    public static function setUpBeforeClass() : void
    {
        $topic = self::create_class_object(midcom_db_topic::class, [
            'component' => 'org.openpsa.directmarketing'
        ]);
        self::$campaign = self::create_class_object(org_openpsa_directmarketing_campaign_dba::class, [
            'node' => $topic->id
        ]);

    }

    public function test_send()
    {
        $message = $this->create_object(org_openpsa_directmarketing_campaign_message_dba::class, [
            'campaign' => self::$campaign->id
        ]);

        $datamanager = datamanager::from_schemadb('file:/org/openpsa/directmarketing/config/schemadb_default_message.inc')
            ->set_storage($message);

        $config = [
            'dm_storage' => $datamanager->get_storage(),
            'link_detector_address' => 'https://openpsa2.org/TOKEN'
        ];
        $this->send($config, $message);
    }

    public function test_send_html()
    {
        $message = $this->create_object(org_openpsa_directmarketing_campaign_message_dba::class, [
            'campaign' => self::$campaign->id,
            'orgOpenpsaObtype' => org_openpsa_directmarketing_campaign_message_dba::EMAIL_HTML
        ]);

        $datamanager = datamanager::from_schemadb('file:/org/openpsa/directmarketing/config/schemadb_default_message.inc')
            ->set_storage($message);

        $config = [
            'dm_storage' => $datamanager->get_storage(),
            'link_detector_address' => 'https://openpsa2.org/TOKEN'
        ];
        $this->send($config, $message);
    }

    private function send(array $config, org_openpsa_directmarketing_campaign_message_dba $message)
    {
        $sender = new \org_openpsa_directmarketing_sender_backend_email($config, $message);

        $person = new \org_openpsa_contacts_person_dba;
        $person->email = 'test@openpsa2.org';

        $member = new \org_openpsa_directmarketing_campaign_member_dba;
        $member->person = $person->id;
        $member->campaign = self::$campaign->id;

        $sender->send($person, $member, 'test', 'test', 'test', 'from@openpsa2.org');

        $this->assertCount(1, \org_openpsa_mail_backend_unittest::$mails);
    }
}
