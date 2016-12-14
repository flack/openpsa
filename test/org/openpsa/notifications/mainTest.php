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
class org_openpsa_notifications_mainTest extends openpsa_testcase
{
    public function test_notify()
    {
        $person = $this->create_object('midcom_db_person', array('email' => 'noreply@openpsa2.org'));
        $person->set_parameter('org.openpsa.notifications', 'net.nehmer.comments:comment_posted', 'email');
        $stat = org_openpsa_notifications::notify('net.nehmer.comments:comment_posted', $person->guid, array());
        $this->assertTrue($stat);
    }

    public function test_load_schemadb()
    {
        $notifications = new org_openpsa_notifications;
        $schemadb = $notifications->load_schemadb();

        $this->assertArrayHasKey('default', $schemadb);
        $this->assertInstanceOf('midcom_helper_datamanager2_schema', $schemadb['default']);
        $this->assertArrayHasKey('net_nehmer_comments_comment_posted', $schemadb['default']->fields);
    }
}
