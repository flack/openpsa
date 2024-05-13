<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Hour report CRUD handler
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_handler_hours_admin extends midcom_baseclasses_components_handler
{
    private function load_datamanager(org_openpsa_expenses_hour_report_dba $report, array $defaults = [], ?string $schema = null) : datamanager
    {
        return datamanager::from_schemadb($this->_config->get('schemadb_hours'))
            ->set_defaults($defaults)
            ->set_storage($report, $schema);
    }

    /**
     * Displays the report creation view.
     */
    public function _handler_create(Request $request, string $handler_id, array &$data, string $schema, ?string $guid = null)
    {
        $report = new org_openpsa_expenses_hour_report_dba();

        $defaults = [
            'person' => midcom_connection::get_user(),
            'date' => time()
        ];

        if ($handler_id == 'hours_create_task') {
            $task = new org_openpsa_projects_task_dba($guid);
            $task->require_do('midgard:create');
            $defaults['task'] = $task->id;
            $defaults['invoiceable'] = $task->hoursInvoiceableDefault;
            $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
            $qb->add_constraint('task', '=', $task->id);
            $result = $qb->execute();
            if (count($result) == 1) {
                $defaults['invoice'] = $result[0]->invoice;
            }
        } elseif ($handler_id == 'hours_create_invoice') {
            $invoice = new org_openpsa_invoices_invoice_dba($guid);
            $invoice->require_do('midgard:create');
            $defaults['invoice'] = $invoice->id;
            $defaults['invoiceable'] = true;
            $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
            $qb->add_constraint('invoice', '=', $invoice->id);
            $qb->add_constraint('task', '<>', 0);
            $result = $qb->execute();
            if (count($result) == 1) {
                $defaults['task'] = $result[0]->task;
            }
        }
        $dm = $this->load_datamanager($report, $defaults, $schema);
        $data['controller'] = $dm->get_controller();
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($dm->get_schema()->get('description'))));

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);
        return $workflow->run($request);
    }

    /**
     * Looks up an hour_report to edit.
     */
    public function _handler_edit(Request $request, string $handler_id, string $guid)
    {
        $report = new org_openpsa_expenses_hour_report_dba($guid);
        $dm = $this->load_datamanager($report);

        midcom::get()->head->set_pagetitle($this->_l10n->get($handler_id));

        $workflow = $this->get_workflow('datamanager', ['controller' => $dm->get_controller()]);
        if ($report->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', [
                'object' => $report,
                'label' => $this->_l10n->get('hour report'),
                'relocate' => false
            ]);
            $workflow->add_dialog_button($delete, $this->router->generate('hours_delete', ['guid' => $guid]));
        }
        return $workflow->run($request);
    }

    /**
     * The delete handler.
     */
    public function _handler_delete(Request $request, string $guid)
    {
        $hour_report = new org_openpsa_expenses_hour_report_dba($guid);
        $options = [
            'object' => $hour_report,
            'relocate' => false
        ];

        try {
            $task = org_openpsa_projects_task_dba::get_cached($hour_report->task);
            $options['success_url'] = $this->router->generate('list_hours_task', ['guid' => $task->guid]);
        } catch (midcom_error $e) {
            $e->log();
        }
        return $this->get_workflow('delete', $options)->run($request);
    }

    /**
     * executes passed action for passed reports & relocates to passed url
     */
    public function _handler_batch(Request $request)
    {
        if ($entries = $request->request->all('entries')) {
            $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
            $qb->add_constraint('id', 'IN', $entries);

            $field = str_replace('uninvoiceable', 'invoiceable', $request->request->get('action'));
            $value = $this->parse_input($request->request, $field);
            foreach ($qb->execute() as $hour_report) {
                if ($hour_report->$field != $value) {
                    $hour_report->$field = $value;
                    $hour_report->update();
                }
            }
        }

        $relocate = $request->request->get('relocate_url', $this->router->generate('index'));
        return new midcom_response_relocate($relocate);
    }

    private function parse_input(ParameterBag $input, string $action)
    {
        if (!in_array($action, ['invoiceable', 'invoice', 'task'])) {
            throw new midcom_error('passed action ' . $action . ' is unknown');
        }
        if ($action == 'invoiceable') {
            return $input->getBoolean('value');
        }
        $selection = $input->get('selection');
        if (empty($selection)) {
            return 0;
        }
        return (int) array_pop($selection);
    }
}
