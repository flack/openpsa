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
class midcom_helper_datamanager2_ajax_autocompleteTest extends openpsa_testcase
{
    public function test_int_field()
    {
        midcom::get()->auth->request_sudo('midcom.helper.datamanager2');
        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice_parameters = array('number' => $invoice->generate_invoice_number());
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba', $invoice_parameters);
        $request = array(
            'component' => 'org.openpsa.invoices',
            'class' => 'org_openpsa_invoices_invoice_dba',
            'id_field' => 'id',
            'searchfields' => array('number'),
        	'titlefield' => 'number',
            'result_headers' => array(
                array(
                    'title' => 'number',
                    'name' => 'number',
                ),
            ),
            'term' => (string) $invoice->number
        );

        $handler = new midcom_helper_datamanager2_ajax_autocomplete($request);
        $res = $handler->get_results();
        midcom::get()->auth->drop_sudo();

        $this->assertEquals(1, sizeof($res));
        $this->assertEquals($res[0]['label'], $invoice->number);
    }

    public function test_parent_field()
    {
        midcom::get()->auth->request_sudo('midcom.helper.datamanager2');
        $project_title = 'PROJECT_TEST_' . __CLASS__ . '_' . time();
        $project = $this->create_object('org_openpsa_projects_project', array('title' => $project_title));

        $task_properties = array(
            'project' => $project->id,
            'title' => 'TASK_TEST_' . __CLASS__ . '_' . time()
        );
        $task = $this->create_object('org_openpsa_projects_task_dba', $task_properties);

        $request = array(
            'component' => 'org.openpsa.projects',
            'class' => 'org_openpsa_projects_task_dba',
            'id_field' => 'id',
            'searchfields' => array('title', 'project.title'),
            'titlefield' => 'title',
            'result_headers' => array(
                array(
                    'title' => 'title',
                    'name' => 'title',
                ),
            ),
            'term' => $project_title
        );

        $handler = new midcom_helper_datamanager2_ajax_autocomplete($request);
        $res = $handler->get_results();
        midcom::get()->auth->drop_sudo();

        $this->assertEquals(1, sizeof($res));
        $this->assertEquals($res[0]['label'], $task->title);
    }
}
