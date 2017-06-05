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
class midcom_helper_datamanager2_storage_midgardTest extends openpsa_testcase
{
    public function test_on_load_data()
    {
        $filename = 'file:/../test/midcom/helper/datamanager2/__files/schemadb_invoice.inc';
        $schemadb = midcom_helper_datamanager2_schema::load_database($filename);
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba', ['description' => 'TEST']);

        $storage = new midcom_helper_datamanager2_storage_midgard($schemadb['default'], $invoice);

        $this->assertEquals('TEST', $storage->_on_load_data('description'));
    }

    public function test_on_store_data()
    {
        $filename = 'file:/../test/midcom/helper/datamanager2/__files/schemadb_invoice.inc';
        $schemadb = midcom_helper_datamanager2_schema::load_database($filename);
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba');

        $storage = new midcom_helper_datamanager2_storage_midgard($schemadb['default'], $invoice);
        $storage->_on_store_data('description', 'TEST');
        $this->assertEquals('TEST', $storage->object->description);
    }

    public function test_on_update_object()
    {
        $filename = 'file:/../test/midcom/helper/datamanager2/__files/schemadb_invoice.inc';
        $schemadb = midcom_helper_datamanager2_schema::load_database($filename);
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba');

        $storage = new midcom_helper_datamanager2_storage_midgard($schemadb['default'], $invoice);
        $storage->_on_store_data('description', 'TEST');

        midcom::get()->auth->request_sudo('midcom.helper.datamanager2');
        $this->assertTrue($storage->_on_update_object(), midcom_connection::get_error_string());
        $invoice->refresh();
        midcom::get()->auth->drop_sudo();

        $this->assertEquals('TEST', $storage->object->description);
        $this->assertEquals('TEST', $invoice->description);
    }
}
