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
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba');
        $salesproject = $this->create_object('org_openpsa_sales_salesproject_dba');
        $relatedto = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales');

        $this->assertTrue(is_a($relatedto, 'org_openpsa_relatedto_dba'));
        $this->register_object($relatedto);
        $this->assertEquals($relatedto->status, org_openpsa_relatedto_dba::CONFIRMED);
        $this->assertEquals($relatedto->fromGuid, $invoice->guid);
        $this->assertEquals($relatedto->fromComponent, 'org.openpsa.invoices');
        $this->assertEquals($relatedto->fromClass, 'org_openpsa_invoices_invoice_dba');
        $this->assertEquals($relatedto->toGuid, $salesproject->guid);
        $this->assertEquals($relatedto->toComponent, 'org.openpsa.sales');
        $this->assertEquals($relatedto->toClass, 'org_openpsa_sales_salesproject_dba');

        $relatedto2 = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales');
        $this->assertEquals($relatedto2->guid, $relatedto->guid);

        $x = null;
        $stat = org_openpsa_relatedto_plugin::create($x, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales');
        $this->assertFalse($stat);

        $stat = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $x, 'org.openpsa.sales');
        $this->assertFalse($stat);

        $relatedto2 = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales', org_openpsa_relatedto_dba::NOTRELATED);
        $this->assertEquals($relatedto2->guid, $relatedto->guid);
        $this->assertEquals($relatedto2->status, org_openpsa_relatedto_dba::NOTRELATED);

        $stat = $relatedto->delete();
        $this->assertTrue($stat);
        midcom::get()->auth->drop_sudo();
    }

    public function test_relatedto2get()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba');
        $salesproject = $this->create_object('org_openpsa_sales_salesproject_dba');
        $relatedto = org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $salesproject, 'org.openpsa.sales');
        midcom::get()->auth->drop_sudo();
        $this->register_object($relatedto);

        $expected = 'org_openpsa_relatedto%5B0%5D%5BtoGuid%5D=' . $salesproject->guid . '&org_openpsa_relatedto%5B0%5D%5BtoComponent%5D=org.openpsa.sales&org_openpsa_relatedto%5B0%5D%5BtoClass%5D=org_openpsa_sales_salesproject_dba&org_openpsa_relatedto%5B0%5D%5Bstatus%5D=120&org_openpsa_relatedto%5B0%5D%5BfromComponent%5D=org.openpsa.invoices&org_openpsa_relatedto%5B0%5D%5BfromClass%5D=org_openpsa_invoices_invoice_dba&org_openpsa_relatedto%5B0%5D%5BfromGuid%5D=' . $invoice->guid;

        $this->assertEquals($expected, org_openpsa_relatedto_plugin::relatedto2get([$relatedto]));
    }
}
