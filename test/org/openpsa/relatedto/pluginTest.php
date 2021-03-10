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
class org_openpsa_relatedto_pluginTest extends openpsa_testcase
{
    public function testCreate()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $relatedto = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales');

        $this->assertInstanceOf(org_openpsa_relatedto_dba::class, $relatedto);
        $this->register_object($relatedto);
        $this->assertEquals(org_openpsa_relatedto_dba::CONFIRMED, $relatedto->status);
        $this->assertEquals($invoice->guid, $relatedto->fromGuid);
        $this->assertEquals('org.openpsa.invoices', $relatedto->fromComponent);
        $this->assertEquals(org_openpsa_invoices_invoice_dba::class, $relatedto->fromClass);
        $this->assertEquals($salesproject->guid, $relatedto->toGuid);
        $this->assertEquals('org.openpsa.sales', $relatedto->toComponent);
        $this->assertEquals(org_openpsa_sales_salesproject_dba::class, $relatedto->toClass);

        $relatedto2 = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales');
        $this->assertEquals($relatedto->guid, $relatedto2->guid);

        $relatedto2 = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales', org_openpsa_relatedto_dba::NOTRELATED);
        $this->assertEquals($relatedto->guid, $relatedto2->guid);
        $this->assertEquals(org_openpsa_relatedto_dba::NOTRELATED, $relatedto2->status);

        $stat = $relatedto->delete();
        $this->assertTrue($stat);
        midcom::get()->auth->drop_sudo();
    }

    public function test_relatedto2get()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $relatedto = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales');
        midcom::get()->auth->drop_sudo();
        $this->register_object($relatedto);

        $expected = 'org_openpsa_relatedto%5B0%5D%5BtoGuid%5D=' . $salesproject->guid . '&org_openpsa_relatedto%5B0%5D%5BtoComponent%5D=org.openpsa.sales&org_openpsa_relatedto%5B0%5D%5BtoClass%5D=org_openpsa_sales_salesproject_dba&org_openpsa_relatedto%5B0%5D%5Bstatus%5D=120&org_openpsa_relatedto%5B0%5D%5BfromComponent%5D=org.openpsa.invoices&org_openpsa_relatedto%5B0%5D%5BfromClass%5D=org_openpsa_invoices_invoice_dba&org_openpsa_relatedto%5B0%5D%5BfromGuid%5D=' . $invoice->guid;

        $this->assertEquals($expected, org_openpsa_relatedto_plugin::relatedto2get([$relatedto]));
    }
}
