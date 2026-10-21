<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Query\Expr\Join;

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
        $task = $invoice = null;

        if ($handler_id == 'hours_create_task') {
            $task = new org_openpsa_projects_task_dba($guid);
            $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
            $qb->get_doctrine()->leftJoin('org_openpsa_invoice_item', 'i', Join::WITH, 'c.id = i.invoice')
                ->andWhere('i.task = :task')
                ->setParameter('task', $task->id);
            $result = $qb->execute();
            if (count($result) == 1) {
                $invoice = $result[0];
            }
        } elseif ($handler_id == 'hours_create_invoice') {
            $invoice = new org_openpsa_invoices_invoice_dba($guid);
            $qb = org_openpsa_projects_task_dba::new_query_builder();
            $qb->get_doctrine()->leftJoin('org_openpsa_invoice_item', 'i', Join::WITH, 'c.id = i.task')
                ->andWhere('i.invoice = :invoice')
                ->setParameter('invoice', $invoice->id);
            $result = $qb->execute();
            if (count($result) == 1) {
                $task = $result[0];
            }
        }
        if ($task?->can_do('midgard:create')) {
            $defaults['task'] = $task->id;
            $defaults['invoiceable'] = $task->hoursInvoiceableDefault;
        }
        if ($invoice?->can_do('midgard:create')) {
            $defaults['invoice'] = $invoice->id;
            $defaults['invoiceable'] = true;
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
     * Edit multiple hour reports at once
     */
    public function _handler_batch_edit(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', $request->query->get('entries', ''))));
        if (empty($ids)) {
            throw new midcom_error_notfound('No hour reports selected');
        }
        $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', $ids);
        $reports = $qb->execute();
        if (empty($reports)) {
            throw new midcom_error_notfound('No hour reports found');
        }

        $batch = new org_openpsa_expenses_batchedit($this->_config->get('schemadb_hours'), $reports);
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('edit %s hour reports'), count($reports)));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $batch->get_controller(),
            'save_callback' => $batch->save(...)
        ]);
        return $workflow->run($request);
    }
}
