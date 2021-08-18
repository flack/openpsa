<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\notifications;

use openpsa_testcase;
use midcom\datamanager\datamanager;
use midcom_db_person;
use org_openpsa_notifications;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class mainTest extends openpsa_testcase
{
    public function test_notify()
    {
        $person = $this->create_object(midcom_db_person::class, ['email' => 'noreply@openpsa2.org']);
        $person->set_parameter('org.openpsa.notifications', 'net.nehmer.comments:comment_posted', 'email');
        $stat = org_openpsa_notifications::notify('net.nehmer.comments:comment_posted', $person->guid, []);
        $this->assertTrue($stat);
    }

    public function test_load_datamanager()
    {
        $notifications = new org_openpsa_notifications;
        $dm = $notifications->load_datamanager();
        $this->assertInstanceOf(datamanager::class, $dm);
        $dm->set_storage(new midcom_db_person);
        $fields = $dm->get_schema()->get('fields');
        $this->assertArrayHasKey('net_nehmer_comments_comment_posted', $fields);
    }
}
