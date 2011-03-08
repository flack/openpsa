<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_db_groupTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('midcom.core');

        $group = new midcom_db_group();
        $stat = $group->create();
        $this->assertTrue($stat);
        $this->register_object($group);

        $group->refresh();
        $this->assertEquals('Group #' . $group->id, $group->official);
        $this->assertEquals('Group #' . $group->id, $group->get_label());

        $group->official = 'TEST GROUP ' . __CLASS__;
        $stat = $group->update();
        $this->assertTrue($stat);

        $this->assertEquals('TEST GROUP ' . __CLASS__, $group->get_label());

        $stat = $group->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }

    /**
     * @depends testCRUD
     */
    public function testMembershipManagement()
    {
        $person = $this->create_object('midcom_db_person');
        $group = $this->create_object('midcom_db_group');

        $_MIDCOM->auth->request_sudo('midcom.core');
        $stat = $group->add_member($person);
        $this->assertTrue($stat);
        $this->assertTrue($group->is_member($person));

        $count = $this->_count_membership_objects($group->id, $person->id);
        $this->assertEquals($count, 1);

        //Make sure we don't create duplicate membership objects
        $stat = $group->add_member($person);
        $this->assertTrue($stat);
        $count = $this->_count_membership_objects($group->id, $person->id);
        $this->assertEquals($count, 1);

        $person->delete();
        $this->assertFalse($group->is_member($person));

        $count = $this->_count_membership_objects($group->id, $person->id);
        $this->assertEquals($count, 0);

        $_MIDCOM->auth->drop_sudo();
    }

    private function _count_membership_objects($gid, $pid)
    {
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $gid);
        $qb->add_constraint('uid', '=', $pid);
        return $qb->count();
    }
}
?>