<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_datamanager2_ajax_autocompleteTest extends openpsa_testcase
{
    public function test_int_field()
    {
        midcom::get('auth')->request_sudo('midcom.helper.datamanager2');
        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice_parameters = array ('number' => $invoice->generate_invoice_number());
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba', $invoice_parameters);
        $request = array
        (
            'component' => 'org.openpsa.invoices',
            'class' => 'org_openpsa_invoices_invoice_dba',
            'id_field' => 'id',
            'searchfields' => array('number'),
        	'titlefield' => 'number',
            'result_headers' => array
            (
                array
                (
                    'title' => 'number',
                    'name' => 'number',
                ),
            ),
            'term' => (string) $invoice->number
        );

        $handler = new midcom_helper_datamanager2_ajax_autocomplete($request);
        $res = $handler->get_results();
        midcom::get('auth')->drop_sudo();

        $this->assertEquals(1, sizeof($res));
        $this->assertEquals($res[0]['label'], $invoice->number);
    }
}
?>