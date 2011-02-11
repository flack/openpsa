<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once('rootfile.php');

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_invoices_invoiceTest extends openpsa_testcase
{
    public function testNumbering()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.invoices');
        $invoice = new org_openpsa_invoices_invoice_dba();
        $next_number = $invoice->generate_invoice_number();
        $this->assertTrue(is_int($next_number));
        $invoice->number = $next_number;
        $stat = $invoice->create();
        $this->assertTrue($stat);
        $this->assertEquals($next_number + 1, $invoice->generate_invoice_number());

        $stat = $invoice->delete();
        $this->assertTrue($stat);
        $this->assertEquals($next_number, $invoice->generate_invoice_number());

        $_MIDCOM->auth->drop_sudo();
    }
}
?>