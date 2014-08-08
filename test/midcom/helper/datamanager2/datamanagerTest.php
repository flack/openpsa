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
class midcom_helper_datamanager2_datamanagerTest extends openpsa_testcase
{
    public function test_set_schema()
    {
        $filename = 'file:/../test/midcom/helper/datamanager2/__files/schemadb_invoice.inc';
        $schemadb = midcom_helper_datamanager2_schema::load_database($filename);
        $dm = new midcom_helper_datamanager2_datamanager($schemadb);

        $this->assertFalse($dm->set_schema('nonexistantkey'));
        $this->assertTrue($dm->set_schema());
        $this->assertEquals('default', $dm->schema_name);
        $this->assertTrue($dm->set_schema('default'));
        $this->assertEquals('default', $dm->schema_name);
    }

    public function test_set_storage()
    {
        $filename = 'file:/../test/midcom/helper/datamanager2/__files/schemadb_invoice.inc';
        $schemadb = midcom_helper_datamanager2_schema::load_database($filename);
        $dm = new midcom_helper_datamanager2_datamanager($schemadb);
        $dm->set_schema();

        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba');
        $this->assertTrue($dm->set_storage($invoice));
        $this->assertTrue(is_a($dm->storage, 'midcom_helper_datamanager2_storage_midgard'));
        $this->assertTrue($dm->set_storage($dm->storage));
    }

    public function test_autoset_storage()
    {
        $filename = 'file:/../test/midcom/helper/datamanager2/__files/schemadb_invoice.inc';
        $schemadb = midcom_helper_datamanager2_schema::load_database($filename);
        $dm = new midcom_helper_datamanager2_datamanager($schemadb);

        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba');
        $this->assertTrue($dm->autoset_storage($invoice));
        $this->assertEquals('default', $dm->schema_name);
        $this->assertTrue(is_a($dm->storage, 'midcom_helper_datamanager2_storage_midgard'));
        $this->assertTrue($dm->autoset_storage($dm->storage));
        $this->assertEquals('default', $dm->schema_name);
    }
}
