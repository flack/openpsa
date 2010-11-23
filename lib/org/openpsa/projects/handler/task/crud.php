<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: crud.php 26679 2010-10-04 16:25:19Z flack $
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
    /**
     * Simple default constructor
     */
    public function __construct()
    {
        $this->_dba_class = 'org_openpsa_projects_task_dba';
        $this->_prefix = 'task';
    }

    public function _load_object($handler_id, $args, &$data)
    {
        $this->_object = call_user_func(array($this->_dba_class, 'get_cached'), $args[0]);
        if (   !$this->_object
            || !$this->_object->guid)
        {
            org_openpsa_core_ui::object_inaccessible($args[0]);
        }
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
                MIDCOM_TOOLBAR_HELPTEXT => null,
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
        
        $_MIDCOM->set_pagetitle($view_title);
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

        if ($this->_object->status == ORG_OPENPSA_TASKSTATUS_CLOSED)
        {
            // TODO: Make POST request
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "task/{$this->_object->guid}/reopen/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('reopen'),
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/folder-expanded.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
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
                    MIDCOM_TOOLBAR_HELPTEXT => null,
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                )
            );
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $expenses_url = $siteconfig->get_node_full_url('org.openpsa.expenses');

        if ($expenses_url)
        {
            org_openpsa_core_ui::enable_jqgrid();
            if ($this->_object->status < ORG_OPENPSA_TASKSTATUS_CLOSED)
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
                    MIDCOM_TOOLBAR_HELPTEXT => null,
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
    function _load_schemadb()
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
    public function _load_parent($handler_id, $args, &$data)
    {
        if (   $this->_mode == 'create'
            && count($args) > 0
            && $args[0] == 'project')
        {
            // This task is to be connected to a project
            $this->_parent = new org_openpsa_projects_project($args[1]);
            if (!$this->_parent)
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
    function _update_breadcrumb($handler_id)
    {
        $tmp = array();
        if ($this->_object)
        {
            $tmp = org_openpsa_projects_viewer::update_breadcrumb_line($this->_object);
        }
        else if ($this->_parent)
        {
            $tmp = org_openpsa_projects_viewer::update_breadcrumb_line($this->_parent);
        }

        switch ($this->_mode)
        {
            case 'update':
                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => "task/edit/{$this->_object->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n_midcom->get('edit'),
                );
                break;
            case 'delete':
                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => "task/delete/{$this->_object->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n_midcom->get('delete'),
                );
                break;
            case 'create':
                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => "",
                    MIDCOM_NAV_NAME => $this->_l10n->get('new task'),
                );
                break;
        }

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    /**
     * Add CSS
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_callback($handler_id, $args, &$data)
    {
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css",
            )
        );
        //need js for chooser-widgets for list of hour - because of dynamic load loading is needed here
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/chooser/jquery.chooser_widget.js");
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/chooser/jquery.chooser_widget.css",
            )
        );

        if ($handler_id == 'task_view')
        {
            $_MIDCOM->load_library('org.openpsa.contactwidget');
            $data['calendar_node'] = midcom_helper_find_node_by_component('org.openpsa.calendar');
        }

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_read($handler_id, &$data)
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
        $mc->add_constraint('status', '<>', ORG_OPENPSA_RELATEDTO_STATUS_NOTRELATED);
        // TODO: fromClass too?
        $mc->execute();

        $relations = $mc->list_keys();
        foreach ($relations as $guid => $empty)
        {
            $booking = new org_openpsa_calendar_event_dba($mc->get_subkey($guid, 'fromGuid'));
            if (!$booking)
            {
                continue;
            }

            if ($mc->get_subkey($guid, 'status') == ORG_OPENPSA_RELATEDTO_STATUS_CONFIRMED)
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


    function _load_defaults()
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
            $task->up = (int) $this->_parent->id;

            // Populate some default data from parent as needed
            $task->orgOpenpsaAccesstype = $this->_parent->orgOpenpsaAccesstype;
            $task->orgOpenpsaOwnerWg = $this->_parent->orgOpenpsaOwnerWg;

        }

        if (! $task->create())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('We operated on this object:', $task);
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Failed to create a new task under project #{$this->_request_data['project']->id}, cannot continue. Error: " . midcom_connection::get_error_string());
            // This will exit.
        }

        $this->_object = new org_openpsa_projects_task_dba($task->id);

        return $this->_object;
    }

    /**
     * Method for adding or updating the task to the MidCOM indexer service.
     *
     * @param $dm Datamanager2 instance containing the object
     */
    public function _index_object(&$dm)
    {
        $indexer = $_MIDCOM->get_service('indexer');

        $nav = new midcom_helper_nav();
        //get the node to fill the required index-data for topic/component
        $node = $nav->get_node($nav->get_current_node());

        $document = $indexer->new_document($dm);
        $document->topic_guid = $node[MIDCOM_NAV_GUID];
        $document->topic_url = $node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $document->component = $node[MIDCOM_NAV_COMPONENT];

        if($indexer->index($document))
        {
            return true;
        }
        return false;
    }
}

?>