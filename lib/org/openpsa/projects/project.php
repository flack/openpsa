<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.projects
 */
class org_openpsa_projects_project extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_project';

    public $contacts = null; //Shorthand access for contact members
    public $resources = null; // --''--


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

    public function __get($property)
    {
        if ($property == 'status_type')
        {
            return org_openpsa_projects_workflow::get_status_type($this->status);
        }
        return parent::__get($property);
    }

    function get_icon()
    {
        return org_openpsa_projects_workflow::get_status_type_icon($this->status_type);
    }

    /**
     * Generate a user-readable label for the task using the task/project hierarchy
     */
    function get_label()
    {
        $label = '';
        $label_elements = array();
        $task = $this;
        while (   !is_null($task)
               && $task = $task->get_parent())
        {
            if (isset($task->title))
            {
                $label_elements[] = $task->title;
            }
        }

        $label_elements = array_reverse($label_elements);
        foreach ($label_elements as $element)
        {
            $label .= "{$element} / ";
        }
        $label .= $this->title;

        return trim($label);
    }

    public function get_parent()
    {
        try
        {
            $project = new org_openpsa_projects_project($this->up);
            return $project;
        }
        catch (midcom_error $e)
        {
            $e->log();
            return null;
        }
    }

    public function get_salesproject()
    {
        return new org_openpsa_sales_salesproject_dba($this->id);
    }

    /**
     * Populates contacts as resources lists
     */
    function get_members()
    {
        if (!$this->guid)
        {
            return false;
        }

        if (!is_array($this->contacts))
        {
            $this->contacts = array();
        }
        if (!is_array($this->resources))
        {
            $this->resources = array();
        }

        $mc = org_openpsa_contacts_role_dba::new_collector('objectGuid', $this->guid);
        $mc->add_value_property('role');
        $mc->add_value_property('person');
        $mc->add_constraint('role', '<>', ORG_OPENPSA_OBTYPE_PROJECTPROSPECT);
        $mc->execute();
        $ret = $mc->list_keys();

        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $guid => $empty)
            {
                switch ($mc->get_subkey($guid, 'role'))
                {
                    case ORG_OPENPSA_OBTYPE_PROJECTCONTACT:
                        $varName = 'contacts';
                        break;
                    default:
                        //fall-trough intentional
                    case ORG_OPENPSA_OBTYPE_PROJECTRESOURCE:
                        $varName = 'resources';
                        break;
                }
                $this->{$varName}[$mc->get_subkey($guid, 'person')] = true;
            }
        }
        return true;
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
        $task_mc = org_openpsa_projects_task_dba::new_collector('project', $this->id);
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
        $task_mc = org_openpsa_projects_task_dba::new_collector('project', $this->id);
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
    public function refresh_from_tasks()
    {
        $update_required = false;

        $task_statuses = array();
        $status_types = array();

        $task_qb = org_openpsa_projects_task_dba::new_query_builder();
        $task_qb->add_constraint('project', '=', $this->id);
        $ret = $task_qb->execute();

        if (sizeof($ret) == 0)
        {
            return;
        }

        foreach ($ret as $task)
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
            $this->status = $new_status;
            $update_required = true;
        }

        if ($update_required)
        {
            debug_add("Some project information needs to be updated, skipping ACL refresh");
            $this->_skip_acl_refresh = true;
            $this->_use_rcs = false;
            $this->_use_activitystream = false;
            return $this->update();
        }
        else
        {
            debug_add("All project information is up-to-date");
            return true;
        }
    }
}
?>