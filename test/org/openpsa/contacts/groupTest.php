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
class org_openpsa_contacts_groupTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.contacts');
        $group = new org_openpsa_contacts_group_dba();

        $group->name = 'TEST NAME';
        $stat = $group->create();
        $this->assertFalse($stat);

        $group->name = 'TEST-NAME';
        $stat = $group->create();
        $this->assertTrue($stat);
        $this->assertEquals('TEST-NAME', $group->get_label());
        $this->assertEquals(ORG_OPENPSA_ACCESSTYPE_PUBLIC, $group->orgOpenpsaAccesstype);

        $group->official = 'TEST OFFICIAL';
        $stat = $group->update();
        $this->assertTrue($stat);
        $this->assertEquals('TEST OFFICIAL', $group->get_label());

        $stat = $group->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }
}
?>