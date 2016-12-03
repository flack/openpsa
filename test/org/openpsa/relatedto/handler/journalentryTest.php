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
class org_openpsa_relatedto_handler_journalentryTest extends openpsa_testcase
{
    protected static $_object;
    protected static $_entry;

    public static function setUpBeforeClass()
    {
        self::create_user(true);
        self::$_object = self::create_class_object('org_openpsa_contacts_person_dba');

        self::$_entry = self::create_class_object('org_openpsa_relatedto_journal_entry_dba', array('linkGuid' => self::$_object->guid));
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $data = $this->run_handler('org.openpsa.contacts', array('__mfa', 'org.openpsa.relatedto', 'journalentry', 'list'));
        $this->assertEquals('____mfa-org.openpsa.relatedto-journal_entry_list', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');

        $data = $this->run_handler('org.openpsa.contacts', array('__mfa', 'org.openpsa.relatedto', 'journalentry', 'create', self::$_object->guid));
        $this->assertEquals('____mfa-org.openpsa.relatedto-journal_entry_create', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');

        $data = $this->run_handler('org.openpsa.contacts', array('__mfa', 'org.openpsa.relatedto', 'journalentry', 'edit', self::$_entry->guid));
        $this->assertEquals('____mfa-org.openpsa.relatedto-journal_entry_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $entry = self::create_class_object('org_openpsa_relatedto_journal_entry_dba', array('linkGuid' => self::$_object->guid));

        midcom::get()->auth->request_sudo('org.openpsa.relatedto');

        $url = $this->run_relocate_handler('org.openpsa.contacts', array('__mfa', 'org.openpsa.relatedto', 'journalentry', 'delete', $entry->guid));
        $this->assertEquals('', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_entry()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');

        $data = $this->run_handler('org.openpsa.contacts', array('__mfa', 'org.openpsa.relatedto', 'journalentry', self::$_object->guid));
        $this->assertEquals('____mfa-org.openpsa.relatedto-journal_entry', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
