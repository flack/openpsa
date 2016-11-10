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
class org_openpsa_directmarketing_campaignTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $campaign = new org_openpsa_directmarketing_campaign_dba();

        $stat = $campaign->create();
        $this->assertFalse($stat);

        $topic = $this->create_object('midcom_db_topic', array('component' => 'org.openpsa.directmarketing'));

        $campaign = new org_openpsa_directmarketing_campaign_dba();
        $campaign->node = $topic->id;
        $campaign->_use_activitystream = false;
        $campaign->_use_rcs = false;

        $stat = $campaign->create();

        $this->assertTrue($stat, midcom_connection::get_error_string());
        $this->register_object($campaign);

        $campaign->title = 'TEST';

        $stat = $campaign->update();
        $this->assertTrue($stat);
        $campaign->refresh();

        $this->assertEquals('TEST', $campaign->title);

        $stat = $campaign->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
