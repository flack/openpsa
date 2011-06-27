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
class org_openpsa_sales_calculatorTest extends openpsa_testcase
{
    protected $_deliverable;

    public function setUp()
    {
        $salesproject = $this->create_object('org_openpsa_sales_salesproject_dba');
        $this->_deliverable = $this->create_object('org_openpsa_sales_salesproject_deliverable_dba', array('salesproject' => $salesproject->id));
    }

    public function testGet_invoice_items()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.sales');

        $project = $this->create_object('org_openpsa_projects_project');
        $task_attributes = array
        (
            'project' => $project->id,
            'agreement' => $this->_deliverable->id
        );
        $task = $this->create_object('org_openpsa_projects_task_dba', $task_attributes);
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba');

        $calculator = new org_openpsa_sales_calculator_default($this->_deliverable);
        $calculator->run();
        $items = $calculator->get_invoice_items($invoice);

        $this->assertTrue(sizeof($items) == 1);
        $this->assertEquals($task->id, $items[0]->task);

        $_MIDCOM->auth->drop_sudo();
    }

}
?>