<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once OPENPSA_TEST_ROOT . 'midcom/helper/datamanager2/__helper/dm2.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_datamanager2_widget_hiddenTest extends openpsa_testcase
{
    public function test_get_default()
    {
        $dm2_helper = new openpsa_test_dm2_helper;
        $widget = $dm2_helper->get_widget('hidden', 'number');

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = ['test_hidden_1' => 5.1];
        $widget = $dm2_helper->get_widget('hidden', 'number');

        $this->assertEquals(5.1, $widget->get_default(), 'nullstorage/default test failed');

        $invoice = new org_openpsa_invoices_invoice_dba;
        $dm2_helper = new openpsa_test_dm2_helper($invoice);
        $widget = $dm2_helper->get_widget('hidden', 'number', ['storage' => 'sum']);

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = ['test_hidden_1' => 5.1];
        $widget = $dm2_helper->get_widget('hidden', 'number');

        $this->assertEquals(5.1, $widget->get_default(), 'create/default test failed');

        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba');
        $dm2_helper = new openpsa_test_dm2_helper($invoice);
        $widget = $dm2_helper->get_widget('hidden', 'number', ['storage' => 'sum']);

        $this->assertEquals(0.0, $widget->get_default(), 'simple test failed');

        $invoice->sum = 5.1;

        $dm2_helper = new openpsa_test_dm2_helper($invoice);
        $widget = $dm2_helper->get_widget('hidden', 'number', ['storage' => 'sum']);

        $this->assertEquals(5.1, $widget->get_default(), 'simple/storage test failed');
    }
}
