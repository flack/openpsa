<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_sales_salesproject_memberTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $salesproject = $this->create_object('org_openpsa_sales_salesproject_dba');
        $person1 = $this->create_object('midcom_db_person');
        $person2 = $this->create_object('midcom_db_person');

        $_MIDCOM->auth->request_sudo('org.openpsa.sales');

        $member1 = new org_openpsa_sales_salesproject_member_dba();
        $member1->salesproject = $salesproject->id;
        $member1->person = $person1->id;
        $stat = $member1->create();
        $this->assertTrue($stat);
        $this->register_object($member1);
        $this->assertEquals(ORG_OPENPSA_OBTYPE_SALESPROJECT_MEMBER, $member1->orgOpenpsaObtype);
        $this->assertEquals(array($person1->id => true), $salesproject->contacts);

        $stat = $member1->delete();
        $this->assertTrue($stat);

        $member2 = new org_openpsa_sales_salesproject_member_dba();
        $member2->salesproject = $salesproject->id;
        $member2->person = $person2->id;
        $stat = $member2->create();
        $this->assertTrue($stat);

        $stat = $salesproject->delete();
        $this->assertTrue($stat);

        $qb = org_openpsa_sales_salesproject_member_dba::new_query_builder();
        $qb->add_constraint('salesproject', '=', $salesproject->id);
        $this->assertEquals(0, $qb->count());

        $_MIDCOM->auth->drop_sudo();
     }
}
?>
