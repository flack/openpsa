<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\invoices\handler\invoice;

use midcom_db_person;
use openpsa_testcase;
use org_openpsa_invoices_invoice_dba;
use org_openpsa_invoices_invoice_item_dba;
use midcom;
use midcom_db_topic;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class actionTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_invoices_invoice_dba $_invoice;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$_invoice = self::create_class_object(org_openpsa_invoices_invoice_dba::class);
        self::create_class_object(org_openpsa_invoices_invoice_item_dba::class, ['invoice' => self::$_invoice->id]);
    }

    public function testHandler_create_cancelation()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = [
            'customerContact' => self::$_person->id,
            'sum' => 300,
            'date' => gmmktime(0, 0, 0, date('n'), date('j'), date('Y')),
            'vat' => 19,
        ];
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class, $data);

        // we got a fresh invoice, it should be cancelable
        $this->assertTrue($invoice->is_cancelable());

        // process
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'id' => $invoice->id,
            'relocate' => true
        ];
        $url = $this->run_relocate_handler('org.openpsa.invoices', ['invoice', 'action', 'create_cancelation']);

        // now we should got a cancelation invoice
        $invoice->refresh();
        $this->assertGreaterThan(0, $invoice->cancelationInvoice, 'Missing cancelation invoice');
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

    /**
     * @todo: Once we have a way to inject config values, we should add a mock object here
     */
    public function testHandler_create_pdf()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $topic = $this->create_object(midcom_db_topic::class, ['component' => 'org.openpsa.invoices']);
        $topic->set_parameter('org.openpsa.invoices', 'invoice_pdfbuilder_class', 'nonexistent');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'id' => self::$_invoice->id,
            'relocate' => true
        ];
        $url = $this->run_relocate_handler($topic, ['invoice', 'action', 'create_pdf']);
        $this->assertEquals('invoice/' . self::$_invoice->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create_payment_warning()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $topic = $this->create_object(midcom_db_topic::class, ['component' => 'org.openpsa.invoices']);
        $topic->set_parameter('org.openpsa.invoices', 'invoice_pdfbuilder_reminder_class', 'nonexistent');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'id' => self::$_invoice->id,
            'relocate' => true
        ];
        $url = $this->run_relocate_handler($topic, ['invoice', 'action', 'create_payment_warning']);
        $this->assertEquals('invoice/' . self::$_invoice->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_mark_sent()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'id' => self::$_invoice->id,
            'relocate' => true
        ];
        $url = $this->run_relocate_handler('org.openpsa.invoices', ['invoice', 'action', 'mark_sent']);
        $this->assertEquals('invoice/' . self::$_invoice->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_mark_paid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'id' => self::$_invoice->id,
            'relocate' => true
        ];
        $url = $this->run_relocate_handler('org.openpsa.invoices', ['invoice', 'action', 'mark_paid']);
        $this->assertEquals('invoice/' . self::$_invoice->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_send_by_mail()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['invoice', 'action', 'send_by_mail', self::$_invoice->guid]);
        $this->assertEquals('invoice_send_by_mail', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_send_payment_reminder()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['invoice', 'action', 'send_payment_reminder', self::$_invoice->guid]);
        $this->assertEquals('invoice_send_payment_reminder', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
