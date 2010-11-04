<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * special case 'project' of class org_openpsa_projects_task_dba
 * @package org.openpsa.projects
 */
class org_openpsa_projects_project extends org_openpsa_projects_task_dba
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_task';

    function __construct($identifier = NULL)
    {
        return parent::__construct($identifier);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }
    
    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    function _on_creating()
    {
        $stat = parent::_on_creating();
        $this->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_PROJECT;
        return $stat;
    }

    /**
     * Helper function to rename vgroups, if they are not found, they are 
     * created instead. The update will only trigger if the project title has changed 
     *
     * @param mixed $vgroup The vgroup, if any
     * @param string $title The title to set
     * @param string $identifier The vgroup identifier
     */
    private function _update_vgroup($vgroup, $title, $identifier)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!is_object($vgroup))
        {
            // Register workgroups here
            debug_add("Registering workgroup: " . $vgroup->name);
            $_MIDCOM->auth->request_sudo('org.openpsa.projects');
            $_MIDCOM->auth->register_virtual_group('org.openpsa.projects', $identifier, $title);
            $_MIDCOM->auth->drop_sudo();
        }
        else if ($vgroup->name != $title)
        {
            //Renaming vgroup is made with delete+register cycle
            debug_add("Deleting previous workgroup: " . $vgroup->name);
            $_MIDCOM->auth->request_sudo('org.openpsa.projects');
            $_MIDCOM->auth->delete_virtual_group($vgroup);
            debug_add("Registering workgroup: " . $title);
            // TODO: localize
            $_MIDCOM->auth->register_virtual_group('org.openpsa.projects', $identifier, $title);
            $_MIDCOM->auth->drop_sudo();
            
        }
        debug_pop();
    }

    /**
     * Helper to remove a vgroup
     *
     * @param midcom_core_group_virtual $vgroup The vgroup to remove
     */
    private function _delete_vgroups()
    {
        $participants_vgroup = new midcom_core_group_virtual("org.openpsa.projects-{$this->guid}");
        $contacts_vgroup = new midcom_core_group_virtual("org.openpsa.projects-{$this->guid}subscribers");

        if (is_object($contacts_vgroup))
        {
            //TODO: This can be a problem for vgroup ACL selector...
            $_MIDCOM->auth->request_sudo('org.openpsa.projects');
            $_MIDCOM->auth->delete_virtual_group($contacts_vgroup);
            $_MIDCOM->auth->drop_sudo();
        }
        if (is_object($participants_vgroup))
        {
            //TODO: This can be a problem for vgroup ACL selector...
            $_MIDCOM->auth->request_sudo('org.openpsa.projects');
            $_MIDCOM->auth->delete_virtual_group($participants_vgroup);
            $_MIDCOM->auth->drop_sudo();
        }
    } 

    function _on_updated()
    {
        // Not finished projects can be active workgroups, finished ones inactive (but still workgroups).
        if (   $this->orgOpenpsaWgtype != ORG_OPENPSA_WGTYPE_NONE
            && $this->status != ORG_OPENPSA_TASKSTATUS_CLOSED)
        {
            $participants_vgroup = new midcom_core_group_virtual("org.openpsa.projects-{$this->guid}");
            $contacts_vgroup = new midcom_core_group_virtual("org.openpsa.projects-{$this->guid}subscribers");

            $this->orgOpenpsaWgtype = ORG_OPENPSA_WGTYPE_ACTIVE;

            $this->_update_vgroup($participants_vgroup, $this->title, $this->guid);
            // TODO: localize
            $this->_update_vgroup($contacts_vgroup, $this->title . ' contacts', $this->guid . 'subscribers');
        }
        else
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("This is a finished project, no workgroups registered");
            debug_pop();
            //Remove workgroups
            $this->_delete_vgroups();

            $this->orgOpenpsaWgtype = ORG_OPENPSA_WGTYPE_INACTIVE;
        }
        parent::_on_updated();
    }

    function _on_deleted()
    {
        //Remove workgroups
        $this->_delete_vgroups();

        parent::_on_deleted();
    }


    /**
     * Helper functions that gets the number of tasks for the different status types
     * 
     * @return array The task status overview
     */
    function get_task_count()
    {
        $numbers = array
        (
            'not_started' => 0,
            'ongoing' => 0,
            'on_hold' => 0,
            'closed' => 0,
            'rejected' => 0
        );
        $task_mc = org_openpsa_projects_project::new_collector('up', $this->id);
        $task_mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $task_mc->add_value_property('status');
        $task_mc->execute();
        $tasks = $task_mc->list_keys();
        foreach ($tasks as $guid => $empty)
        {
            $type = org_openpsa_projects_workflow::get_status_type($task_mc->get_subkey($guid, 'status'));
            $numbers[$type]++;
        }
        return $numbers;
    }
    
    /**
     * Helper functions that gets the number of tasks for the different status types
     * 
     * @return array The task hours overview
     */
    function get_task_hours()
    {
        $numbers = array
        (
            'plannedHours' => 0,
            'reportedHours' => 0
        );
        $task_mc = org_openpsa_projects_project::new_collector('up', $this->id);
        $task_mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $task_mc->add_value_property('plannedHours');
        $task_mc->add_value_property('reportedHours');
        $task_mc->execute();
        $tasks = $task_mc->list_keys();
        foreach ($tasks as $guid => $empty)
        {
            $values = $task_mc->get($guid);
            $numbers['plannedHours'] += $values['plannedHours'];
            $numbers['reportedHours'] += $values['reportedHours'];
        }
        return $numbers;
    }

    /**
     * This function sets project information according to the situation of its tasks
     *
     * This adjusts the timeframe if necessary and tries to determine the project's
     * status according to the current task situation 
     */
    function _refresh_from_tasks()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $update_required = false;

        $task_statuses = array();
        $status_types = array();
        $found_ongoing = false;

        $task_qb = org_openpsa_projects_task_dba::new_query_builder();
        $task_qb->add_constraint('up', '=', $this->id);
        $ret = $task_qb->execute();
        
        if ( sizeof($ret) == 0)
        {
            return;
        }

        foreach($ret as $task)
        {
            if ($task->start < $this->start)
            {
                $this->start = $task->start;
                $update_required = true;
            }
            if ($task->end > $this->end)
            {
                $this->end = $task->end;
                $update_required = true;
            }

            if (!array_key_exists($task->status_type, $status_types))
            {
                $status_types[$task->status_type] = true;
            }
            //Simple way to handle accepted and various "under work" statuses
            if (!array_key_exists($task->status, $task_statuses))
            {
                $task_statuses[$task->status] = 0;
            }
            $task_statuses[$task->status]++;
        }

        $new_status = null;

        if (sizeof($task_statuses) == 1)
        {
            // If all tasks are of the same type, that is the type to use then
            $new_status = key($task_statuses);
        }
        else
        {
            switch ($this->status_type)
            {
                case 'rejected':
                    // If there's an ongoing task, the project seems to have resumed
                    if (array_key_exists('ongoing', $status_types))
                    {
                        $new_status = ORG_OPENPSA_TASKSTATUS_REOPENED;
                    }
                    // There are pending tasks, so maybe the project is back to start
                    else if (array_key_exists('not_started', $status_types))
                    {
                        $new_status = ORG_OPENPSA_TASKSTATUS_PROPOSED;
                    }
                    // Sanity check
                    else if (!array_key_exists('rejected', $status_types))
                    {
                        //Blocker task
                        if (array_key_exists('on_hold', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_ONHOLD;
                        }
                        //Work seems to have been finished
                        else if (array_key_exists('closed', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_ONHOLD;
                        }
                    }
                    break;
                case 'not_started':
                    //Work seems to have been started
                    if (array_key_exists('ongoing', $status_types))
                    {
                        $new_status = ORG_OPENPSA_TASKSTATUS_STARTED;
                    }
                    // Or is on hold
                    else if (array_key_exists('on_hold', $status_types))
                    {
                        $new_status = ORG_OPENPSA_TASKSTATUS_ONHOLD;
                    }
                    // Or is even finished already
                    else if (array_key_exists('closed', $status_types)
                             && !array_key_exists('not_started', $status_types))
                    {
                        $new_status = ORG_OPENPSA_TASKSTATUS_COMPLETED;
                    }
                    break;
                case 'ongoing':
                    //Only do something if there are no ongoing tasks
                    if (!array_key_exists('ongoing', $status_types))
                    {
                        //Blocker task
                        if (array_key_exists('on_hold', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_ONHOLD;
                        }
                        //Project is in limbo: Some tasks are finished, others didn't begin yet
                        else if (array_key_exists('not_started', $status_types)
                                 || array_key_exists('closed', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_ONHOLD;
                        }
                        //Back to start: Someone withdrew acceptance
                        else if (array_key_exists('not_started', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_PROPOSED;
                        }
                        //Work seems to have been finished
                        else if (array_key_exists('closed', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_ONHOLD;
                        }
                    }
                    break;
                case 'closed':
                    //Something new came up, reopen
                    if (   array_key_exists('not_started', $status_types)
                        || array_key_exists('ongoing', $status_types))
                    {
                        $new_status = ORG_OPENPSA_TASKSTATUS_REOPENED;
                    }
                    //Sanity check
                    else if (!array_key_exists('closed', $status_types))
                    {
                        if (array_key_exists('on_hold', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_ONHOLD;
                        }
                    }
                    break;
                case 'on_hold':
                    //If no task is on hold, we have to look for something else
                    if (!array_key_exists('on_hold', $status_types))
                    {
                        // check if work has resumed
                        if (array_key_exists('ongoing', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_STARTED;
                        }
                        // If nothing is closed, ongoing or on hold, let's try not_started
                        else if (    array_key_exists('not_started', $status_types)
                                 && !array_key_exists('closed', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_PROPOSED;
                        }
                        // If nothing is not_started, ongoing or on hold, let's try closed
                        else if (    array_key_exists('closed', $status_types)
                                 && !array_key_exists('not_started', $status_types))
                        {
                            $new_status = ORG_OPENPSA_TASKSTATUS_COMPLETED;
                        }
                    }
            }
        }

        if (!is_null($new_status)
            && $this->status != $new_status)
        {
            org_openpsa_projects_workflow::create_status($this, $new_status);
        }

        if ($update_required)
        {
            debug_add("Some project information needs to be updated, skipping ACL refresh");
            $this->_skip_acl_refresh = true;
            $this->_use_rcs = false;
            $this->_use_activitystream = false;
            debug_pop();
            return $this->update();
        }
        else
        {
            debug_add("All project information is up-to-date");
            debug_pop();
            return true;
        }
    }

}
?>