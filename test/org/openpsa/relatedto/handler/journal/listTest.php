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
class org_openpsa_relatedto_handler_journal_listTest extends openpsa_testcase
{
    protected static $_object;
    protected static $_entry;

    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
        self::$_object = self::create_class_object(org_openpsa_contacts_person_dba::class);

        self::$_entry = self::create_class_object(org_openpsa_relatedto_journal_entry_dba::class, ['linkGuid' => self::$_object->guid]);
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $data = $this->run_handler('org.openpsa.contacts', ['__mfa', 'org.openpsa.relatedto', 'journalentry', 'list', time()]);
        $this->assertEquals('journal_entry_list', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_xml()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $data = $this->run_handler('org.openpsa.contacts', ['__mfa', 'org.openpsa.relatedto', 'journalentry', 'xml']);
        $this->assertEquals('journal_entry_xml', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_object()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');

        $data = $this->run_handler('org.openpsa.contacts', ['__mfa', 'org.openpsa.relatedto', 'journalentry', self::$_object->guid]);
        $this->assertEquals('journal_entry', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
