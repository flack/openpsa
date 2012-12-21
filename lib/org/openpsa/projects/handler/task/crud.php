<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Projects create/read/update/delete task handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_crud extends midcom_baseclasses_components_handler_crud
{
    public $_dba_class = 'org_openpsa_projects_task_dba';
    public $_prefix = 'task';

    public function _load_object($handler_id, array $args, array &$data)
    {
        $this->_object = new $this->_dba_class($args[0]);
    }

    /**
     * Method for getting URL to the current object.
	 *
     * @return string URL to the current object
     */
    public function _get_object_url()
    {
        return 'task/' . $this->_object->guid . '/';
    }

    /**
     * Method for adding the supported operations into the toolbar.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _populate_toolbar($handler_id)
    {
        if (   $this->_mode == 'create'
            || $this->_mode == 'update')
        {
            org_openpsa_helpers::dm2_savecancel($this);
        }
        else if ($this->_mode == 'delete')
        {
            org_openpsa_helpers::dm2_savecancel($this, 'delete');
        }
        if ($this->_mode == 'create')
        {
            return;
        }

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "task/edit/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );
        if ($this->_object->reportedHours == 0)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "task/delete/{$this->_object->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:delete'),
                    MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                )
            );
        }

        if ($this->_mode == 'read')
        {
            $this->_populate_read_toolbar($handler_id);
        }

        switch ($handler_id)
        {
            case 'task_edit':
                $this->_view_toolbar->disable_item("task/edit/{$this->_object->guid}/");
                break;
            case 'task_delete':
                $this->_view_toolbar->disable_item("task/delete/{$this->_object->guid}/");
                break;
        }
    }

    /**
     * Method for updating title for current object and handler
     *
     * @param mixed $handler_id The ID of the handler.
     */
    public function _update_title($handler_id)
    {
        switch ($this->_mode)
        {
            case 'create':
                $view_title = $this->_l10n->get('create task');
                break;
            case 'read':
                $view_title = $this->_object->get_label();
                break;
            case 'update':
                $view_title = sprintf($this->_l10n_midcom->get('edit %s'), $this->_object->get_label());
                break;
            case 'delete':
                $view_title = sprintf($this->_l10n_midcom->get('delete %s'), $this->_object->get_label());
                break;
        }

        midcom::get('head')->set_pagetitle($view_title);
    }

    /**
     * Special helper for adding the supported operations from read into the toolbar.
     *
     * @param mixed $handler_id The ID of the handler.
     */
    private function _populate_read_toolbar($handler_id)
    {
        if (!$this->_object->can_do('midgard:update'))
        {
             return;
        }

        if ($this->_object->status == org_openpsa_projects_task_status_dba::CLOSED)
        {
            // TODO: Make POST request
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "task/{$this->_object->guid}/reopen/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('reopen'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/folder-expanded.png',
                )
            );
        }
        else if ($this->_object->status_type == 'ongoing')
        {
            // TODO: Make POST request
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "task/{$this->_object->guid}/complete/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('mark completed'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
                )
            );
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $expenses_url = $siteconfig->get_node_full_url('org.openpsa.expenses');

        if ($expenses_url)
        {
            midcom_helper_datamanager2_widget_autocomplete::add_head_elements();
            org_openpsa_widgets_grid::add_head_elements();
            if ($this->_object->status < org_openpsa_projects_task_status_dba::CLOSED)
            {
                $this->_view_toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => $expenses_url . "hours/create/hour_report/{$this->_object->guid}/",
                        MIDCOM_TOOLBAR_LABEL => sprintf
                        (
                            $this->_l10n_midcom->get('create %s'),
                            $this->_l10n->get('hour report')
                        ),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
                    )
                );
            }
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $expenses_url . "hours/task/all/{$this->_object->guid}",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('hour reports'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/scheduled_and_shown.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'h',
                )
            );
        }
        org_openpsa_relatedto_plugin::add_button($this->_view_toolbar, $this->_object->guid);
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_task'));
    }

    /**
     * Method for loading parent object for an object that is to be created.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _load_parent($handler_id, array $args, array &$data)
    {
        if (   $this->_mode == 'create'
            && count($args) > 0
            && $args[0] == 'project')
        {
            // This task is to be connected to a project
            try
            {
                $this->_parent = new org_openpsa_projects_project($args[1]);
            }
            catch (midcom_error $e)
            {
                return false;
            }

            // Copy resources and contacts from project
            $this->_parent->get_members();

            $this->_defaults['resources'] = array_keys($this->_parent->resources);
            $this->_defaults['contacts'] = array_keys($this->_parent->contacts);
        }
        return;
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id
     */
    public function _update_breadcrumb($handler_id)
    {
        $tmp = array();
        if ($this->_object)
        {
            org_openpsa_projects_viewer::add_breadcrumb_path($this->_object, $this);
        }
        else if ($this->_parent)
        {
            org_openpsa_projects_viewer::add_breadcrumb_path($this->_parent, $this);
        }

        switch ($this->_mode)
        {
            case 'update':
                $this->add_breadcrumb("task/edit/{$this->_object->guid}/", $this->_l10n_midcom->get('edit'));
                break;
            case 'delete':
                $this->add_breadcrumb("task/delete/{$this->_object->guid}/", $this->_l10n_midcom->get('delete'));
                break;
            case 'create':
                $this->add_breadcrumb("", $this->_l10n->get('new task'));
                break;
        }
    }

    /**
     * Add CSS
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_callback($handler_id, array $args, array &$data)
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");

        //need js for chooser-widgets for list of hour - because of dynamic load loading is needed here
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/chooser/jquery.chooser_widget.js");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/chooser/jquery.chooser_widget.css");

        if ($handler_id == 'task_view')
        {
            org_openpsa_widgets_contact::add_head_elements();
            $data['calendar_node'] = midcom_helper_misc::find_node_by_component('org.openpsa.calendar');
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_read($handler_id, array &$data)
    {
        $data['datamanager'] =& $this->_datamanager;

        $data['task_bookings'] = $this->_list_bookings();

        midcom_show_style('show-task');
    }

    private function _list_bookings()
    {
        $task_booked_time = 0;
        $task_booked_percentage = 100;

        $bookings = array
        (
            'confirmed' => array(),
            'suspected' => array(),
        );
        $mc = new org_openpsa_relatedto_collector($this->_object->guid, 'org_openpsa_calendar_event_dba');
        $mc->add_value_property('status');
        $mc->add_constraint('status', '<>', org_openpsa_relatedto_dba::NOTRELATED);
        // TODO: fromClass too?
        $mc->execute();

        $relations = $mc->list_keys();
        foreach ($relations as $guid => $empty)
        {
            try
            {
                $booking = new org_openpsa_calendar_event_dba($mc->get_subkey($guid, 'fromGuid'));
            }
            catch (midcom_error $e)
            {
                continue;
            }

            if ($mc->get_subkey($guid, 'status') == org_openpsa_relatedto_dba::CONFIRMED)
            {
                $bookings['confirmed'][] = $booking;
                $task_booked_time += ($booking->end - $booking->start) / 3600;
            }
            else
            {
                $bookings['suspected'][] = $booking;
            }
        }

        usort($bookings['confirmed'], array('self', '_sort_by_time'));
        usort($bookings['suspected'], array('self', '_sort_by_time'));

        $task_booked_time = round($task_booked_time);

        if ($this->_object->plannedHours != 0)
        {
            $task_booked_percentage = round(100 / $this->_object->plannedHours * $task_booked_time);
        }

        $this->_request_data['task_booked_percentage'] = $task_booked_percentage;
        $this->_request_data['task_booked_time'] = $task_booked_time;

        return $bookings;
    }

    /**
     * Code to sort array of events by $event->start, from smallest to greatest
     *
     * Used by $this->_list_bookings()
     */
    private static function _sort_by_time($a, $b)
    {
        $ap = $a->start;
        $bp = $b->start;
        if ($ap > $bp)
        {
            return 1;
        }
        if ($ap < $bp)
        {
            return -1;
        }
        return 0;
    }

    public function _load_defaults()
    {
        $this->_defaults['manager'] = midcom_connection::get_user();
    }

    /**
     * This is what Datamanager calls to actually create a task
     */
    function & dm2_create_callback(&$controller)
    {
        $task = new org_openpsa_projects_task_dba();

        if ($this->_parent)
        {
            // Add the task to the project
            $task->project = (int) $this->_parent->id;

            // Populate some default data from parent as needed
            $task->orgOpenpsaAccesstype = $this->_parent->orgOpenpsaAccesstype;
            $task->orgOpenpsaOwnerWg = $this->_parent->orgOpenpsaOwnerWg;
        }

        if (! $task->create())
        {
            debug_print_r('We operated on this object:', $task);
            throw new midcom_error("Failed to create a new task under project #{$this->_request_data['project']->id}. Error: " . midcom_connection::get_error_string());
        }

        $this->_object = new org_openpsa_projects_task_dba($task->id);

        return $this->_object;
    }

    /**
     * Method for adding or updating the task to the MidCOM indexer service.
     *
     * @param &$dm Datamanager2 instance containing the object
     */
    public function _index_object(&$dm)
    {
        //Ugly workaround to http://trac.openpsa2.org/ticket/31
        $this->_object->refresh_status();

        $indexer = new org_openpsa_projects_midcom_indexer($this->_topic);
        return $indexer->index($dm);
    }
}
?>