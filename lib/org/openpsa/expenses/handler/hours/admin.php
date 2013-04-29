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
     * The Controller of the report used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller;

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
     * The defaults to use for the new report.
     *
     * @var array
     */
    private $_defaults = array();

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['controller'] =& $this->_controller;
        $this->_request_data['schema'] =& $this->_schema;
        $this->_request_data['schemadb'] =& $this->_schemadb;
    }

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
        $this->_defaults['task'] = $this->_request_data['task'];
        $this->_defaults['person'] = midcom_connection::get_user();
        $this->_defaults['date'] = time();

        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->defaults = $this->_defaults;
        $this->_controller->callback_object =& $this;
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
    }

    /**
     * DM2 creation callback
     */
    public function & dm2_create_callback (&$controller)
    {
        $this->_hour_report = new org_openpsa_projects_hour_report_dba();

        if ($task = $controller->formmanager->get_value('task'))
        {
            $this->_hour_report->task = $task;
        }
        else if ($this->_request_data['task'])
        {
            $this->_hour_report->task = $this->_request_data['task'];
        }
        if (! $this->_hour_report->create())
        {
            debug_print_r('We operated on this object:', $this->_hour_report);
            throw new midcom_error("Failed to create a new hour_report under hour_report group #{$this->_request_data['task']}. Error: " . midcom_connection::get_error_string());
        }

        return $this->_hour_report;
    }

    /**
     * Displays the report creation view.
     *
     * If create privileges apply, we relocate to the edit view
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_load_schemadb();
        $data['selected_schema'] = $args[0];
        if (!array_key_exists($data['selected_schema'], $this->_schemadb))
        {
            throw new midcom_error_notfound('The requested schema ' . $args[0] . ' was not found in the schemadb');
        }
        $this->_schema = $data['selected_schema'];

        if (count($args) > 1)
        {
            $task = new org_openpsa_projects_task_dba($args[1]);
            $task->require_do('midgard:create');
            $data['task'] = $task->id;
            $this->_add_toolbar_items($task);
        }
        else
        {
            midcom::get('auth')->require_valid_user();
            $data['task'] = 0;
        }

        $this->_load_create_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $this->_hour_report->modify_hours_by_time_slot();
                $task = org_openpsa_projects_task_dba::get_cached($this->_hour_report->task);
                return new midcom_response_relocate("hours/task/" . $task->guid . "/");

            case 'cancel':
                if (count($args) > 1)
                {
                    return new midcom_response_relocate("hours/task/" . $task->guid . "/");
                }
                else
                {
                    return new midcom_response_relocate('');
                }
        }

        $this->_prepare_request_data();

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $data['view_title'] = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description));
        midcom::get('head')->set_pagetitle($data['view_title']);
        $this->_update_breadcrumb_line($data['view_title']);
    }

    /**
     * Helper to populate the toolbar
     *
     * @param org_openpsa_projects_task_dba $task The parent task
     */
    private function _add_toolbar_items(org_openpsa_projects_task_dba $task)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');

        if ($projects_url)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $projects_url . "task/{$task->guid}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('show task %s'), $task->title),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'g',
                )
            );
        }
    }

    /**
     * Shows the create form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        midcom_show_style('hours_create');
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
        $task = org_openpsa_projects_task_dba::get_cached($this->_hour_report->task);

        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_hour_report);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for hour_report {$this->_hour_report->id}.");
        }

        switch ($this->_controller->process_form())
        {
            case 'save':
                $this->_hour_report->modify_hours_by_time_slot();
                // *** FALL-THROUGH ***
            case 'cancel':
                return new midcom_response_relocate("hours/task/" . $task->guid . "/");
        }

        $this->_prepare_request_data();

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "hours/delete/{$this->_hour_report->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            )
        );

        $this->_add_toolbar_items($task);

        $this->_view_toolbar->bind_to($this->_hour_report);

        midcom::get('metadata')->set_request_metadata($this->_hour_report->metadata->revised, $this->_hour_report->guid);

        midcom::get('head')->set_pagetitle($this->_l10n->get($handler_id));

        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id The handler ID
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $task = false;

        if (isset($this->_hour_report->task))
        {
            $task = org_openpsa_projects_task_dba::get_cached($this->_hour_report->task);
        }
        else if (!empty($this->_request_data['task']))
        {
            $task = org_openpsa_projects_task_dba::get_cached($this->_request_data['task']);
        }

        if ($task)
        {
            $this->add_breadcrumb("hours/task/" . $task->guid, $task->get_label());
            $this->add_breadcrumb("", $this->_l10n->get($handler_id));
        }
    }

    /**
     * Shows the hour_report edit form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('hours_edit');
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
        $this->_hour_report = new org_openpsa_projects_hour_report_dba($args[0]);
        $this->_hour_report->require_do('midgard:delete');

        $this->_controller = midcom_helper_datamanager2_handler::get_delete_controller();

        switch ($this->_controller->process_form())
        {
            case 'delete':
                // Deletion confirmed.
                if (! $this->_hour_report->delete())
                {
                    throw new midcom_error("Failed to delete hour report {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
                }

                // Delete ok, relocating to welcome.
                return new midcom_response_relocate('');

            case 'cancel':
                // Redirect to view page.
                return new midcom_response_relocate('');
        }

        $this->_load_schemadb();
        $dm = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        $dm->autoset_storage($this->_hour_report);
        $data['datamanager'] =& $dm;
        $data['controller'] = $this->_controller;

        $this->_update_breadcrumb_line($handler_id);

        $this->_view_toolbar->bind_to($this->_hour_report);

        midcom::get('metadata')->set_request_metadata($this->_hour_report->metadata->revised, $this->_hour_report->guid);
    }

    /**
     * Shows the delete hour_report form
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, array &$data)
    {
        midcom_show_style('hours_delete');
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
        //get url to relocate
        $relocate = "/";
        if (isset($_POST['relocate_url']))
        {
            $relocate = $_POST['relocate_url'];
        }
        else
        {
            debug_print_r('no relocate url was passed ', $_POST);
        }

        //check if reports are passed
        if (isset($_POST['entries']))
        {
            //iterate through reports
            foreach ($_POST['entries'] as $report_id => $void)
            {
                $hour_report = new org_openpsa_projects_hour_report_dba($report_id);
                switch ($_POST['action'])
                {
                    case 'mark_invoiceable':
                        $hour_report->invoiceable = true;
                        break;
                    case 'mark_uninvoiceable':
                        $hour_report->invoiceable = false;
                        break;
                    case 'change_invoice':
                        $id = $this->_get_autocomplete_selection();
                        if ($id != 0)
                        {
                            $hour_report->invoice = $id;
                        }
                        break;
                    case 'change_task':
                        $id = $this->_get_autocomplete_selection();
                        if ($id != 0)
                        {
                            $hour_report->task = $id;
                        }
                        break;
                    default:
                        throw new midcom_error('passed action ' . $_POST['action'] . ' is unknown');
                }
                $hour_report->update();
            }
        }
        else
        {
            debug_print_r('No reports passed to action handler', $_POST);
        }

        return new midcom_response_relocate($relocate);
    }

    private function _get_autocomplete_selection()
    {
        $selection = $_POST['batch_grid_id'] . '__' . $_POST['action'] . '_selection';
        if (empty($_POST[$selection]))
        {
            return 0;
        }
        $selection = json_decode($_POST[$selection]);
        return (int) array_pop($selection);
    }
}
?>