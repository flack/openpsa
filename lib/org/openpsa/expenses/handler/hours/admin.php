<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Hour report CRUD handler
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_handler_hours_admin extends midcom_baseclasses_components_handler
{
    /**
     * The hour report
     *
     * @var org_openpsa_projects_hour_report_dba
     */
    private $_hour_report;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     */
    private $_schemadb;

    /**
     * The schema to use for the new article.
     *
     * @var string
     */
    private $_schema = 'default';

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_hours'));
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     */
    private function _load_create_controller()
    {
        $defaults['task'] = $this->_request_data['task'];
        $defaults['person'] = midcom_connection::get_user();
        $defaults['date'] = time();

        $controller = midcom_helper_datamanager2_controller::create('create');
        $controller->schemadb =& $this->_schemadb;
        $controller->schemaname = $this->_schema;
        $controller->defaults = $defaults;
        $controller->callback_object =& $this;
        if (!$controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
        return $controller;
    }

    /**
     * DM2 creation callback
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_hour_report = new org_openpsa_projects_hour_report_dba();

        if ($task = $controller->formmanager->get_value('task')) {
            $this->_hour_report->task = $task;
        } elseif ($this->_request_data['task']) {
            $this->_hour_report->task = $this->_request_data['task'];
        }
        if (!$this->_hour_report->create()) {
            debug_print_r('We operated on this object:', $this->_hour_report);
            throw new midcom_error("Failed to create a new hour_report under task #{$this->_request_data['task']}: " . midcom_connection::get_error_string());
        }

        return $this->_hour_report;
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
        $this->_load_schemadb();
        $data['selected_schema'] = $args[0];
        if (!array_key_exists($data['selected_schema'], $this->_schemadb)) {
            throw new midcom_error_notfound('The requested schema ' . $args[0] . ' was not found in the schemadb');
        }
        $this->_schema = $data['selected_schema'];

        if (count($args) > 1) {
            $task = new org_openpsa_projects_task_dba($args[1]);
            $task->require_do('midgard:create');
            $data['task'] = $task->id;
        } else {
            midcom::get()->auth->require_valid_user();
            $data['task'] = 0;
        }

        $data['controller'] = $this->_load_create_controller();
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)));

        $workflow = $this->get_workflow('datamanager2', array('controller' => $data['controller']));
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
        $this->_hour_report = new org_openpsa_projects_hour_report_dba($args[0]);

        $this->_load_schemadb();
        $data['controller'] = midcom_helper_datamanager2_controller::create('simple');
        $data['controller']->schemadb =& $this->_schemadb;
        $data['controller']->set_storage($this->_hour_report);
        if (!$data['controller']->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 controller instance for hour_report {$this->_hour_report->id}.");
        }

        midcom::get()->head->set_pagetitle($this->_l10n->get($handler_id));

        $workflow = $this->get_workflow('datamanager2', array('controller' => $data['controller']));
        if ($this->_hour_report->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', array(
                'object' => $this->_hour_report,
                'label' => $this->_l10n->get('hour report')
            ));
            $workflow->add_dialog_button($delete, "hours/delete/{$this->_hour_report->guid}/");
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
        $hour_report = new org_openpsa_projects_hour_report_dba($args[0]);
        $options = array('object' => $hour_report);

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
            $qb = org_openpsa_projects_hour_report_dba::new_query_builder();
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
        if (!in_array($input['action'], array('invoiceable', 'invoice', 'task'))) {
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
