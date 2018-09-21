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
 * @property integer $person
 * @property integer $task
 * @property integer $orgOpenpsaObtype Used to a) distinguish OpenPSA objects in QB b) store object "subtype" (project vs task etc)
 * @package org.openpsa.projects
 */
class org_openpsa_projects_task_resource_dba extends midcom_core_dbaobject
{
    const RESOURCE = 6006;
    const CONTACT = 6007;

    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_task_resource';

    public $_use_activitystream = false;
    public $_use_rcs = false;

    private function find_duplicates()
    {
        $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
        $qb->add_constraint('person', '=', (int)$this->person);
        $qb->add_constraint('task', '=', (int)$this->task);
        $qb->add_constraint('orgOpenpsaObtype', '=', (int)$this->orgOpenpsaObtype);

        if ($this->id) {
            $qb->add_constraint('id', '<>', (int)$this->id);
        }

        return ($qb->count() > 0);
    }

    public function _on_creating()
    {
        return (!$this->find_duplicates());
    }

    /**
     * Add a member to parents if necessary
     *
     * @param org_openpsa_projects_task_dba $object The object for which we search the parent
     */
    private function add_resource_to_parent($object)
    {
        $parent = $object->get_parent();
        if (!$parent) {
            return;
        }

        if ($parent instanceof org_openpsa_projects_project) {
            org_openpsa_contacts_role_dba::add($parent->guid, $this->person, $this->orgOpenpsaObtype);
            return;
        }

        $mc = self::new_collector('task', $parent->id);
        $mc->add_constraint('orgOpenpsaObtype', '=', $this->orgOpenpsaObtype);
        $mc->add_constraint('person', '=', $this->person);
        $mc->execute();
        if ($mc->count() > 0) {
            //Resource is already present, aborting
            return;
        }

        $new_resource = new self();
        $new_resource->person = $this->person;
        $new_resource->orgOpenpsaObtype = $this->orgOpenpsaObtype;
        $new_resource->task = $parent->id;
        $new_resource->create();
    }

    /**
     * Remove a member from parent resources if necessary
     *
     * @param org_openpsa_projects_task_dba $object The object for which we search the parent
     */
    private function remove_resource_from_parent($object)
    {
        $parent = $object->get_parent();
        if (!$parent) {
            return;
        }

        $mc = self::new_collector('person', $this->person);
        $mc->add_constraint('orgOpenpsaObtype', '=', $this->orgOpenpsaObtype);
        $mc->add_constraint('task.project', 'INTREE', $parent->id);
        $mc->execute();
        if ($mc->count() > 0) {
            //Resource is still present in sibling tasks, aborting
            return;
        }

        $qb = self::new_query_builder();
        $qb->add_constraint('person', '=', $this->person);
        $qb->add_constraint('orgOpenpsaObtype', '=', $this->orgOpenpsaObtype);
        $qb->add_constraint('task', '=', $parent->id);

        foreach ($qb->execute() as $result) {
            $result->delete();
        }
    }

    public function _on_deleted()
    {
        $task = new org_openpsa_projects_task_dba($this->task);
        $this->remove_resource_from_parent($task);
        if ($personobject = midcom::get()->auth->get_user($this->person)) {
            $task->unset_privilege('midgard:read', $personobject->id);
            $task->unset_privilege('midgard:create', $personobject->id);
            $task->unset_privilege('midgard:update', $personobject->id);
        }
    }

    public function _on_created()
    {
        // Add resources to the parent task/project
        $task = new org_openpsa_projects_task_dba($this->task);
        $this->add_resource_to_parent($task);

        if ($this->person) {
            if ($this->orgOpenpsaObtype == self::RESOURCE) {
                org_openpsa_projects_workflow::propose($task, $this->person);
            }

            if ($personobject = midcom::get()->auth->get_user($this->person)) {
                $this->set_privilege('midgard:read', $personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->set_privilege('midgard:delete', $personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->set_privilege('midgard:update', $personobject->id, MIDCOM_PRIVILEGE_ALLOW);

                $task->set_privilege('midgard:read', $personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                // Resources must be permitted to create hour/expense reports into tasks
                $task->set_privilege('midgard:create', $personobject->id, MIDCOM_PRIVILEGE_ALLOW);
                //For declines etc they also need update...
                $task->set_privilege('midgard:update', $personobject->id, MIDCOM_PRIVILEGE_ALLOW);
            } else {
                debug_add('Person ' . $this->person . ' could not be resolved to user, skipping privilege assignment');
            }
        }
    }

    public function _on_updating()
    {
        return (!$this->find_duplicates());
    }
}
