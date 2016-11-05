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
class org_openpsa_invoices_handler_invoice_actionTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_invoice;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        self::$_invoice = self::create_class_object('org_openpsa_invoices_invoice_dba');
        self::create_class_object('org_openpsa_invoices_invoice_item_dba', array('invoice' => self::$_invoice->id));
    }

    public function testHandler_create_cancelation()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = array
        (
            'customerContact' => self::$_person->id,
            'sum' => 300,
            'date' => gmmktime(0, 0, 0, date('n'), date('j'), date('Y')),
            'vat' => 19,
        );
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba', $data);

        // we got a fresh invoice, it should be cancelable
        $this->assertTrue($invoice->is_cancelable());

        // process
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array
        (
            'id' => $invoice->id,
            'relocate' => true
        );
        $url = $this->run_relocate_handler('org.openpsa.invoices', array('invoice', 'action', 'create_cancelation'));

        // now we should got a cancelation invoice
        $invoice->refresh();
        $this->assertTrue(($invoice->cancelationInvoice > 0), 'Missing cancelation invoice');
        $cancelation_invoice = new org_openpsa_invoices_invoice_dba($invoice->cancelationInvoice);
        $this->register_object($cancelation_invoice);

        // check the backlink
        $canceled_invoice = $cancelation_invoice->get_canceled_invoice();
        $this->assertEquals($invoice->id, $canceled_invoice->id);

        // check url after cancelation
        $cancelation_invoice_url = 'invoice/' . $cancelation_invoice->guid . '/';
        $this->assertEquals($cancelation_invoice_url, $url, 'After processing the cancelation invoice, this should relocate to "' . $cancelation_invoice_url . '"!');

        // the cancelation should have the same vat and reversed sum
        $this->assertEquals($invoice->vat, $cancelation_invoice->vat, 'Wrong vat for cancelation invoice');
        $reverse_sum = $invoice->sum * (-1);
        $this->assertEquals($reverse_sum, $cancelation_invoice->sum, 'Wrong sum for cancelation invoice');

        // the invoice should be marked as canceled now, the cancelation should be paid (because original was unsent)
        $this->assertEquals('paid', $cancelation_invoice->get_status());
        $this->assertEquals('canceled', $invoice->get_status());

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_mark_sent()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array
        (
            'id' => self::$_invoice->id,
            'relocate' => true
        );
        $url = $this->run_relocate_handler('org.openpsa.invoices', array('invoice', 'action', 'mark_sent'));
        $this->assertEquals('', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_mark_paid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array
        (
            'id' => self::$_invoice->id,
            'relocate' => true
        );
        $url = $this->run_relocate_handler('org.openpsa.invoices', array('invoice', 'action', 'mark_paid'));
        $this->assertEquals('', $url);

        midcom::get()->auth->drop_sudo();
    }
}
