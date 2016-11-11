<?php
/**
 * Converter script converting projects/salesprojects to 9.0beta structure
 */

class project_converter
{
    private $_project;
    private $_salesproject;

    private $_new_object;

    public function __construct($project)
    {
        $this->_project = $project;
    }

    public function execute()
    {
        $this->_output('Processing ' . $this->_project->title);

        $this->_create_from_project();
        $this->_update_project_members();
        $this->_cleanup_task_statuses();
        $this->_update_tasks();
        $this->_move_parameters($this->_project, $this->_new_object);
        $this->_move_attachments($this->_project, $this->_new_object);
        $this->_move_privileges($this->_project, $this->_new_object);

        if (empty($this->_salesproject)) {
            $this->_find_salesproject();
        }
        if (!empty($this->_salesproject)) {
            $this->_process_salesproject();
        }

        $this->_update_relations($this->_project->guid);

        $this->_commit('delete', $this->_project);
        $this->_output('Done.');
    }

    public function set_salesproject($salesproject)
    {
        $this->_salesproject = $salesproject;
    }

    private function _commit($action, &$object)
    {
        if (!$object->$action()) {
            $this->_output('Could not ' . $action . ' ' . get_class($object) . ', reason: ' . midcom_connection::get_error_string());
        }
    }

    private function _move_parameters(&$source, &$target)
    {
        $params = $source->list_parameters();

        if (count($params) === 0) {
            return;
        }

        foreach ($params as $parameter) {
            if (!$target->set_parameter($parameter->domain, $parameter->name, $parameter->value)) {
                $this->_output('Failed to move parameter ' . $parameter->guid);
            }
            $this->_commit('delete', $parameter);
        }
    }

    private function _move_attachments(&$source, &$target)
    {
        foreach ($source->list_attachments() as $attachment) {
            $attachment->parentguid = $target->guid;
            $this->_commit('update', $attachment);
        }
    }

    private function _move_privileges(&$source, &$target)
    {
        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('objectguid', '=', $source->guid);
        $privileges = $qb->execute();
        foreach ($privileges as $privilege) {
            $privilege->objectguid = $target->guid;
            $this->_commit('update', $privilege);
        }
    }

    private function _output($message, $show_mgd_error = true)
    {
        echo $message . "\n";
        flush();
    }

    private function _cleanup_task_statuses()
    {
        $qb = new midgard_query_builder('org_openpsa_task_status');
        $qb->add_constraint('task', '=', $this->_project->id);
        $statuses = $qb->execute();
        foreach ($statuses as $status) {
            $this->_commit('delete', $status);
        }
    }

    private function _update_project_members()
    {
        $qb = new midgard_query_builder('org_openpsa_task_resource');
        $qb->add_constraint('task', '=', $this->_project->id);
        $members = $qb->execute();
        foreach ($members as $member) {
            $role = new org_openpsa_role();
            $role->person = $member->person;
            $role->objectGuid = $this->_new_object->guid;
            $role->role = $member->orgOpenpsaObtype;
            $this->_commit('create', $role);
            $this->_commit('delete', $member);
        }
    }

    private function _update_salesproject_members()
    {
        $qb = new midgard_query_builder('org_openpsa_task_resource');
        $qb->add_constraint('task', '=', $this->_salesproject->id);
        $members = $qb->execute();
        foreach ($members as $member) {
            $role = new org_openpsa_role();
            $role->person = $member->person;
            $role->objectGuid = $this->_new_object->guid;
            $role->role = $member->orgOpenpsaObtype;
            $this->_commit('create', $role);
            $this->_commit('delete', $member);
        }
    }

    private function _update_tasks()
    {
        $qb = new midgard_query_builder('org_openpsa_task');
        $qb->add_constraint('up', '=', $this->_project->id);
        $tasks = $qb->execute();
        foreach ($tasks as $task) {
            $task->project = $this->_new_object->id;
            $task->up = 0;
            $this->_commit('update', $task);
        }
    }

    private function _process_salesproject()
    {
        $this->_copy_from_salesproject();
        $this->_update_deliverables();
        $this->_update_salesproject_members();
        $this->_move_parameters($this->_salesproject, $this->_new_object);
        $this->_move_attachments($this->_salesproject, $this->_new_object);
        $this->_move_privileges($this->_salesproject, $this->_new_object);
        $this->_update_relations($this->_salesproject->guid);
        $this->_commit('delete', $this->_salesproject);
    }

    private function _find_salesproject()
    {
        $qb = new midgard_query_builder('org_openpsa_relatedto');
        $qb->add_constraint('toClass', '=', 'org_openpsa_sales_salesproject_dba');
        $qb->add_constraint('fromGuid', '=', $this->_project->guid);

        $relations = $qb->execute();
        foreach ($relations as $relation) {
            try {
                $this->_salesproject = new org_openpsa_salesproject_old($relation->toGuid);
            } catch (Exception $e) {
                $this->_output('Failed to load salesproject ' . $relation->toGuid . ' reason: ' . $e->getMessage());
            }
            $this->_commit('delete', $relation);
        }
    }

    private function _update_deliverables()
    {
        $qb = new midgard_query_builder('org_openpsa_salesproject_deliverable');
        $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
        $deliverables = $qb->execute();
        foreach ($deliverables as $deliverable) {
            $deliverable->salesproject = $this->_new_object->id;
            $this->_commit('update', $deliverable);
        }
    }

    private function _update_relations($guid)
    {
        $qb = new midgard_query_builder('org_openpsa_relatedto');
        $qb->add_constraint('toGuid', '=', $guid);
        $relations = $qb->execute();
        foreach ($relations as $relation) {
            $relation->toGuid = $this->_new_object->guid;
            $this->_commit('update', $relation);
        }
        $qb = new midgard_query_builder('org_openpsa_relatedto');
        $qb->add_constraint('fromGuid', '=', $guid);
        $relations = $qb->execute();
        foreach ($relations as $relation) {
            $relation->fromGuid = $this->_new_object->guid;
            $this->_commit('update', $relation);
        }
    }

    private function _copy_from_salesproject()
    {
        try {
            $this->_new_object = new org_openpsa_salesproject($this->_new_object->id);
        } catch (Exception $e) {
            $this->_output('Failed to cast ID ' . $this->_new_object->id . ' to salesproject, reason: ' . $e->getMessage());
            die;
        }

        $this->_output('Merging data from salesproject ' . $this->_salesproject->title, false);

        $property_map = array(
            'title' => 'title',
            'description' => 'description',
            'start' => 'start',
            'end' => 'end',
            'code' => 'code',
            'status' => 'status',
            'customer' => 'customer',
            'owner' => 'owner',
        );
        foreach ($property_map as $source => $destination) {
            if (   $source == 'start'
                && $this->_new_object->start > 0
                && $this->_new_object->start < $this->_salesproject->start) {
                continue;
            } elseif (   $source == 'end'
                     && $this->_new_object->end > $this->_salesproject->end) {
                continue;
            } elseif (   $source == 'customer'
                     || $source == 'owner') {
                if ($this->_salesproject->$source == 0) {
                    continue;
                }
            }
            $this->_new_object->$destination = $this->_salesproject->$source;
        }
        $this->_commit('update', $this->_new_object);
    }

    private function _create_from_project()
    {
        $this->_new_object = new org_openpsa_project();

        $property_map = array(
            'title' => 'title',
            'description' => 'description',
            'start' => 'start',
            'end' => 'end',
            'projectCode' => 'code',
            'status' => 'status',
            'customer' => 'customer',
            'manager' => 'manager',
            'plannedHours' => 'plannedHours',
            'reportedHours' => 'reportedHours',
            'approvedHours' => 'approvedHours',
            'invoicedHours' => 'invoicedHours',
            'invoiceableHours' => 'invoiceableHours',
            'orgOpenpsaAccesstype' => 'orgOpenpsaAccesstype',
            'orgOpenpsaWgtype' => 'orgOpenpsaWgtype',
            'orgOpenpsaOwnerWg' => 'orgOpenpsaOwnerWg',
        );
        foreach ($property_map as $source => $destination) {
            $this->_new_object->$destination = $this->_project->$source;
        }
        $this->_commit('create', $this->_new_object);
    }
}
