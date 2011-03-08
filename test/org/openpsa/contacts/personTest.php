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
class org_openpsa_contacts_personTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.contacts');
        $person = new org_openpsa_contacts_person_dba();

        $person->lastname = 'TEST PERSON ' . __CLASS__;
        $stat = $person->create();
        $this->assertTrue($stat);
        $this->register_object($person);
        $this->assertEquals(ORG_OPENPSA_OBTYPE_PERSON, $person->orgOpenpsaObtype);
        $this->assertEquals('rname', $person->get_label_property());

        $person->firstname = 'FIRSTNAME';
        $stat = $person->update();
        $this->assertTrue($stat);
        $this->assertEquals('TEST PERSON ' . __CLASS__ . ', FIRSTNAME', $person->get_label());

        $stat = $person->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }
}
?>