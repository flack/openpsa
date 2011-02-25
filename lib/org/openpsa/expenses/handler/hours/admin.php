<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Hour report create handler
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
    private $_hour_report = null;

    /**
     * The Controller of the report used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new article.
     *
     * @var string
     */
    private $_schema = 'default';

    /**
     * The defaults to use for the new report.
     *
     * @var Array
     */
    private $_defaults = Array();

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
        $this->_schemadb =& midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_hours'));
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
    function & dm2_create_callback (&$controller)
    {
        $this->_hour_report = new org_openpsa_projects_hour_report_dba();
        if ($this->_request_data['task'])
        {
            $this->_hour_report->task = $this->_request_data['task'];
        }
        else
        {
            $this->_hour_report->task = (int) $_POST['task'];
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        //load component here to be able to access its constants
        $_MIDCOM->componentloader->load('org.openpsa.projects');

        $this->_load_schemadb();
        $data['selected_schema'] = $args[0];
        if (!array_key_exists($data['selected_schema'], $this->_schemadb))
        {
            throw new midcom_error_notfound('The requested schema ' . $args[0] . ' was not found in the schemadb');
        }
        $this->_schema =& $data['selected_schema'];

        if (count($args) > 1)
        {
            $parent = new org_openpsa_projects_task_dba($args[1]);
            $parent->require_do('midgard:create');
            $data['task'] = $parent->id;
            $this->_add_toolbar_items($parent);
        }
        else
        {
            $_MIDCOM->auth->require_valid_user();
            $data['task'] = 0;
        }

        $this->_load_create_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $this->_hour_report->modify_hours_by_time_slot();
                if (count($args) > 1)
                {
                    $_MIDCOM->relocate("hours/task/" . $parent->guid . "/");
                }
                else
                {
                    $_MIDCOM->relocate("hours/edit/{$this->_hour_report->guid}/");
                }
                // This will exit.

            case 'cancel':
                if (count($args) > 1)
                {
                    $_MIDCOM->relocate("hours/task/" . $parent->guid . "/");
                }
                else
                {
                    $_MIDCOM->relocate('');
                }
                // This will exit.
        }

        $this->_prepare_request_data();

        if ($this->_hour_report)
        {
            $_MIDCOM->set_26_request_metadata($this->_hour_report->metadata->revised, $this->_hour_report->guid);
        }

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $data['view_title'] = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description));
        $_MIDCOM->set_pagetitle($data['view_title']);
        $this->_update_breadcrumb_line($data['view_title']);
    }

    /**
     * Helper to populate the toolbar
     *
     * @param mixed &$parent The parent object or false
     */
    private function _add_toolbar_items(&$parent)
    {
        if (empty($parent->guid))
        {
            return;
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');

        if ($projects_url)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $projects_url . "task/{$parent->guid}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('show task %s'), $parent->title),
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_hour_report = new org_openpsa_projects_hour_report_dba($args[0]);

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
                // Reindex the article
                //$indexer = $_MIDCOM->get_service('indexer');
                //net_nemein_wiki_viewer::index($this->_request_data['controller']->datamanager, $indexer, $this->_topic);
                // *** FALL-THROUGH ***
            case 'cancel':
                $task = new org_openpsa_projects_task_dba($this->_hour_report->task);
                $_MIDCOM->relocate("hours/task/" . $task->guid . "/");
                // This will exit.
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

        $parent = $this->_hour_report->get_parent();
        $this->_add_toolbar_items($parent);

        $this->_view_toolbar->bind_to($this->_hour_report);

        $_MIDCOM->set_26_request_metadata($this->_hour_report->metadata->revised, $this->_hour_report->guid);

        $_MIDCOM->set_pagetitle($this->_l10n->get($handler_id));

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
            $task = new org_openpsa_projects_task_dba($this->_hour_report->task);
        }
        if (isset($this->_request_data['task']))
        {
            $task = new org_openpsa_projects_task_dba($this->_request_data['task']);
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_hour_report = new org_openpsa_projects_hour_report_dba($args[0]);
        $this->_hour_report->require_do('midgard:delete');

        if (array_key_exists('org_openpsa_expenses_deleteok', $_REQUEST))
        {
            // Deletion confirmed.
            if (! $this->_hour_report->delete())
            {
                throw new midcom_error("Failed to delete hour report {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            // Delete ok, relocating to welcome.
            $_MIDCOM->relocate('');
            // This will exit.
        }

        if (array_key_exists('org_openpsa_expenses_deletecancel', $_REQUEST))
        {
            // Redirect to view page.
            $_MIDCOM->relocate('');
            // This will exit()
        }

        $this->_load_schemadb();
        $dm = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        $dm->autoset_storage($this->_hour_report);
        $data['datamanager'] =& $dm;

        $this->_update_breadcrumb_line($handler_id);

        $this->_view_toolbar->bind_to($this->_hour_report);

        $_MIDCOM->set_26_request_metadata($this->_hour_report->metadata->revised, $this->_hour_report->guid);
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_batch($handler_id, array $args, array &$data)
    {
        //get url to relocate
        $relocate = "/";
        if(isset($_POST['relocate_url']))
        {
            $relocate = $_POST['relocate_url'];
        }
        else
        {
            debug_print_r('no relocate url was passed ', $_POST);
        }
        //check if reports are passed
        if (isset($_POST['report']))
        {
            //iterate through reports
            foreach ($_POST['report'] as $report_id => $void)
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
                        if (is_array($_POST['org_openpsa_expenses_invoice_chooser_widget_selections']))
                        {
                            foreach ($_POST['org_openpsa_expenses_invoice_chooser_widget_selections'] as $id)
                            {
                                if ($id != 0)
                                {
                                    $hour_report->invoice = $id;
                                    break;
                                }
                            }
                        }
                        break;
                    case 'change_task':
                        if (is_array($_POST['org_openpsa_expenses_task_chooser_widget_selections']))
                        {
                            foreach ($_POST['org_openpsa_expenses_task_chooser_widget_selections'] as $id)
                            {
                                if ($id != 0)
                                {
                                    $hour_report->task = $id;
                                    break;
                                }
                            }
                        }
                        break;
                    default:
                        throw new midcom_error('passed Action ' . $_POST['action'] . ' is unknown');
                }
                $hour_report->update();
            }
        }
        else
        {
            debug_print_r('No Reports passed to action handler', $_POST);
        }

        $_MIDCOM->relocate($relocate);
    }
}
?>