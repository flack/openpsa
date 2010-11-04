<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped access to the MgdSchema class, keep logic here
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_task_resource_dba extends midcom_core_dbaobject
{

    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_task_resource';

    var $_personobject;

    function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;

        return parent::__construct($id);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    function get_parent_guid_uncached()
    {
        if ($this->task != 0)
        {
            $parent = new org_openpsa_projects_task_dba($this->task);

            if ($parent->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_PROJECT)
            {
                // The parent is a project instead
                $parent = new org_openpsa_projects_project($this->task);
            }

            return $parent->guid;
        }
        else
        {
            return null;
        }
    }

    function _add_to_buddylist_of($account)
    {
        if (!$_MIDCOM->auth->user)
        {
            return false;
        }
        $account = new midcom_db_person($account);
        $user = $_MIDCOM->auth->user->get_storage();
        
        $mc = org_openpsa_contacts_buddy_dba::new_collector('account', (string) $account->guid);
        $mc->add_constraint('buddy', '=', (string) $this->_personobject->guid);
        $mc->add_constraint('blacklisted', '=', false);
        $mc->execute();

        if ($mc->count() == 0)
        {
            // Cache the association to buddy list of the sales project owner
            $buddy = new org_openpsa_contacts_buddy_dba();
            $buddy->account = $account->guid;
            $buddy->buddy = $this->_personobject->guid;
            $buddy->isapproved = false;
            return $buddy->create();
        }
    }

    function _find_duplicates()
    {
        $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
        $qb->add_constraint('person', '=', (int)$this->person);
        $qb->add_constraint('task', '=', (int)$this->task);
        $qb->add_constraint('orgOpenpsaObtype', '=', (int)$this->orgOpenpsaObtype);

        if ($this->id)
        {
            $qb->add_constraint('id', '<>', (int)$this->id);
        }

        $dupes = $qb->execute();
        if (count($dupes) > 0)
        {
            return true;
        }
        return false;
    }

    function _on_creating()
    {
        if ($this->_find_duplicates())
        {
            return false;
        }

        return parent::_on_creating();
    }

    /**
     * Helper function that adds a member to parents if necessary
     *
     * @param org_openpsa_projects_task_dba $object The object for which we search the parent
     */
    function add_resource_to_parent(&$object)
    {
        $parent = $object->get_parent();
        if (!$parent)
        {
            return;
        }

        $mc = self::new_collector('task', $parent->id);
        $mc->add_constraint('orgOpenpsaObtype', '=', $this->orgOpenpsaObtype);
        $mc->add_constraint('person', '=', $this->person);
        $mc->execute();
        if ($mc->count() > 0)
        {
            //Resource is already present, aborting
            return;
        }

        $new_resource = new org_openpsa_projects_task_resource_dba();
        $new_resource->person = $this->person;
        $new_resource->orgOpenpsaObtype = $this->orgOpenpsaObtype;
        $new_resource->task = $parent->id;
        $new_resource->create();
    }

    /**
     * Helper function that removes a member from parent resources if necessary
     *
     * @param org_openpsa_projects_task_dba $object The object for which we search the parent
     */
    function remove_resource_from_parent(&$object)
    {
        $parent = $object->get_parent();
        if (!$parent)
        {
            return;
        }

        $mc = self::new_collector('person', $this->person);
        $mc->add_constraint('orgOpenpsaObtype', '=', $this->orgOpenpsaObtype);
        $mc->add_constraint('task.up', 'INTREE', $parent->id);
        $mc->execute();
        if ($mc->count() > 0)
        {
            //Resource is still present in silbling tasks, aborting
            return;
        }

        $qb = self::new_query_builder();
        $qb->add_constraint('person', '=', $this->person);
        $qb->add_constraint('orgOpenpsaObtype', '=', $this->orgOpenpsaObtype);
        $qb->add_constraint('task', '=', $parent->id);

        $results = $qb->execute();

        foreach($results as $result)
        {
            $result->delete();
        }

    }

    function _on_deleted()
    {
        $task = new org_openpsa_projects_task_dba($this->task);
        $this->remove_resource_from_parent($task);
        return true;
    }

    function _on_created()
    {
        // Add resources to the parent task/project
        $task = new org_openpsa_projects_task_dba($this->task);
        $this->add_resource_to_parent($task);

        if ($this->person)
        {
            if ($this->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_PROJECTRESOURCE)
            {
                org_openpsa_projects_workflow::propose($task, $this->person);
            }
            $this->_personobject = self::pid_to_obj($this->person);

            if (   !$this->_personobject
                || !is_object($this->_personobject))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('Person ' . $this->person . ' could not be resolved to user, skipping privilege assignment');
                debug_pop();

                //This is sufficient for the _add_to_buddylist_of calls later on
                $this->_personobject = midcom_db_person::get_cached($this->person);
                if (   !$this->_personobject
                    || !$this->_personobject->guid)
                {
                    return false;
                }
            }
            else
            {
                $this->set_privilege('midgard:read', $this->_personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->set_privilege('midgard:delete', $this->_personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->set_privilege('midgard:update', $this->_personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->set_privilege('midgard:read', $this->_personobject->id, MIDCOM_PRIVILEGE_ALLOW);

                // Ensure resources can read regardless of if this is a vgroup
                $task->set_privilege('midgard:read', $this->_personobject->id, MIDCOM_PRIVILEGE_ALLOW);
    
                if ($task->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_TASK)
                {
                    // Resources must be permitted to create hour/expense reports into tasks
                    $task->set_privilege('midgard:create', $this->_personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                    //For declines etc they also need update...
                    $task->set_privilege('midgard:update', $this->_personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                }
            }

            // Add resource to manager's buddy list
            $this->_add_to_buddylist_of($task->manager);

            // Add resource to other resources' buddy lists
            $mc = org_openpsa_projects_task_resource_dba::new_collector('task', (int) $this->task);
            $mc->add_value_property('person');
            $mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTRESOURCE);
            $mc->add_constraint('id', '<>', (int) $this->id);
            $mc->execute();
            $resources = $mc->list_keys();
            foreach ($resources as $guid => $resource)
            {
                $this->_add_to_buddylist_of($mc->get_subkey($guid, 'person'));
            }
        }
    }

    function _on_updating()
    {
        if ($this->_find_duplicates())
        {
            return false;
        }

        return parent::_on_updating();
    }

    static function pid_to_obj($pid)
    {
        return $_MIDCOM->auth->get_user($pid);
    }

    static function get_resource_tasks($key = 'id', $list_finished = false)
    {
        $task_array = array();
        if (!$_MIDCOM->auth->user)
        {
            return $task_array;
        }

        $mc = org_openpsa_projects_task_resource_dba::new_collector('person', (int) $_MIDGARD['user']);
        $mc->add_value_property('task');
        $mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTRESOURCE);
        $mc->add_constraint('task.orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_PROJECT);
        $mc->add_constraint('task.start', '<=', time());

        if (!$list_finished)
        {
            $mc->add_constraint( 'task.status', '<', ORG_OPENPSA_TASKSTATUS_COMPLETED);
        }
        $mc->execute();

        $resources = $mc->list_keys();
        $i = 0;
        foreach ($resources as $resource => $task_id)
        {
            $task = new org_openpsa_projects_task_dba($mc->get_subkey($resource, 'task'));
            $i++;
            if (!$task)
            {
                continue;
            }

            $task_array[$task->$key] = $task->get_label();
        }
        asort($task_array);
        return $task_array;
    }
}
?>