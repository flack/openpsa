<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\invoices\handler;

use openpsa_testcase;
use midcom;
use org_openpsa_invoices_invoice_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class gotoTest extends openpsa_testcase
{
    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
    }

    public function testHandler_goto()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $number = (new org_openpsa_invoices_invoice_dba)->generate_invoice_number();
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class, ['number' => $number]);

        $_GET = ['query' => $number];
        $url = $this->run_relocate_handler('org.openpsa.invoices', ['goto']);
        $this->assertEquals('invoice/' . $invoice->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_goto_not_found()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $url = $this->run_relocate_handler('org.openpsa.invoices', ['goto']);
        $this->assertEquals('', $url);

        midcom::get()->auth->drop_sudo();
    }
}
