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
class org_openpsa_invoices_invoice_billing_dataTest extends openpsa_testcase
{
    protected static $_contact;

    public static function setUpBeforeClass()
    {
        $attributes = array(
           'street' => 'TEST STREET'
        );
        self::$_contact = self::create_class_object('org_openpsa_contacts_person_dba');
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $data = new org_openpsa_invoices_billing_data_dba();
        $data->linkGuid = self::$_contact->guid;
        $data->useContactAddress = true;
        $stat = $data->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());
        $this->register_object($data);

        $parent = $data->get_parent();
        $this->assertEquals($parent->guid, self::$_contact->guid);

        self::$_contact->refresh();
        $this->assertEquals(self::$_contact->street, $data->street);

        $data->vat = 12;
        $data->due = 12;
        $stat = $data->update();
        $this->assertTrue($stat);

        self::$_contact->refresh();
        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice->customerContact = self::$_contact->id;
        $invoice_data = $invoice->get_billing_data();

        $this->assertEquals($data->guid, $invoice_data->guid);
        $this->assertEquals($data->vat, $invoice->get_default('vat'));
        $this->assertEquals($data->due, $invoice->get_default('due'));

        $stat = $data->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
