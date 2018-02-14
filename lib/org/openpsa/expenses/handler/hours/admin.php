<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Hour report CRUD handler
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_handler_hours_admin extends midcom_baseclasses_components_handler
{
    /**
     * Loads datamanager
     *
     * @return \midcom\datamanager\datamanager
     */
    private function load_datamanager(org_openpsa_expenses_hour_report_dba $report, $defaults = [], $schema = null)
    {
        return datamanager::from_schemadb($this->_config->get('schemadb_hours'))
            ->set_defaults($defaults)
            ->set_storage($report, $schema);
    }

    /**
     * Displays the report creation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $report = new org_openpsa_expenses_hour_report_dba();

        $defaults = [
            'person' => midcom_connection::get_user(),
            'date' => time()
        ];

        if (count($args) > 1) {
            $task = new org_openpsa_projects_task_dba($args[1]);
            $task->require_do('midgard:create');
            $defaults['task'] = $task->id;
        }
        $dm = $this->load_datamanager($report, $defaults, $args[0]);
        $data['controller'] = $dm->get_controller();
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($dm->get_schema()->get('description'))));

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);
        return $workflow->run();
    }

    /**
     * Looks up an hour_report to edit.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $report = new org_openpsa_expenses_hour_report_dba($args[0]);
        $dm = $this->load_datamanager($report);

        midcom::get()->head->set_pagetitle($this->_l10n->get($handler_id));

        $workflow = $this->get_workflow('datamanager', ['controller' => $dm->get_controller()]);
        if ($report->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', [
                'object' => $report,
                'label' => $this->_l10n->get('hour report')
            ]);
            $workflow->add_dialog_button($delete, "hours/delete/{$report->guid}/");
        }
        return $workflow->run();
    }

    /**
     * The delete handler.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $hour_report = new org_openpsa_expenses_hour_report_dba($args[0]);
        $options = ['object' => $hour_report];

        try {
            $task = org_openpsa_projects_task_dba::get_cached($hour_report->task);
            $options['success_url'] = 'hours/task/' . $task->guid . '/';
        } catch (midcom_error $e) {
            $e->log();
        }
        return $this->get_workflow('delete', $options)->run();
    }

    /**
     * executes passed action for passed reports & relocates to passed url
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_batch($handler_id, array $args, array &$data)
    {
        if (!empty($_POST['entries'])) {
            $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
            $qb->add_constraint('id', 'IN', $_POST['entries']);

            $_POST['action'] = str_replace('uninvoiceable', 'invoiceable', $_POST['action']);
            $value = $this->parse_input($_POST);
            $field = $_POST['action'];
            foreach ($qb->execute() as $hour_report) {
                if ($hour_report->$field != $value) {
                    $hour_report->$field = $value;
                    $hour_report->update();
                }
            }
        }

        $relocate = isset($_POST['relocate_url']) ? $_POST['relocate_url'] : "/";
        return new midcom_response_relocate($relocate);
    }

    private function parse_input(array $input)
    {
        if (!in_array($input['action'], ['invoiceable', 'invoice', 'task'])) {
            throw new midcom_error('passed action ' . $input['action'] . ' is unknown');
        }
        if ($input['action'] == 'invoiceable') {
            return !empty($input['value']);
        }
        if (empty($input['selection'])) {
            return 0;
        }
        return (int) array_pop($input['selection']);
    }
}
