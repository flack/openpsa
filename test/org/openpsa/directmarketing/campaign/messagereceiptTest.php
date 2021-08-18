<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\directmarketing\campaign;

use openpsa_testcase;
use midcom;
use midcom_db_topic;
use org_openpsa_directmarketing_campaign_dba;
use org_openpsa_directmarketing_campaign_message_dba;
use org_openpsa_directmarketing_campaign_messagereceipt_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class messagereceiptTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $topic = $this->create_object(midcom_db_topic::class, ['component' => 'org.openpsa.directmarketing']);
        $campaign = $this->create_object(org_openpsa_directmarketing_campaign_dba::class, ['node' => $topic->id]);
        $message = $this->create_object(org_openpsa_directmarketing_campaign_message_dba::class, ['campaign' => $campaign->id]);
        $receipt = new org_openpsa_directmarketing_campaign_messagereceipt_dba();

        $stat = $receipt->create();
        $this->assertFalse($stat);

        $receipt = new org_openpsa_directmarketing_campaign_messagereceipt_dba();
        $receipt->message = $message->id;
        $stat = $receipt->create();
        $this->assertTrue($stat);

        $this->register_object($receipt);

        $receipt->token = 'TEST';

        $stat = $receipt->update();
        $this->assertTrue($stat);
        $receipt->refresh();

        $this->assertEquals('TEST', $receipt->token);

        $stat = $receipt->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
