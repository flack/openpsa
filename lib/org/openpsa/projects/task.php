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
 * @property integer $up
 * @property integer $project
 * @property integer $start
 * @property integer $end
 * @property string $title
 * @property string $description
 * @property float $plannedHours
 * @property integer $status cache of last status
 * @property integer $agreement
 * @property integer $customer
 * @property integer $manager
 * @property float $reportedHours
 * @property float $invoicedHours
 * @property float $invoiceableHours
 * @property boolean $hoursInvoiceableDefault Are hours invoiceable by default ?
 * @property integer $priority
 * @property integer $orgOpenpsaAccesstype Shortcut for various ACL scenarios
 * @property integer $orgOpenpsaObtype Used to a) distinguish OpenPSA objects in QB b) store object "subtype" (project vs task etc)
 * @property string $orgOpenpsaOwnerWg The "owner" workgroup of this object
 * @package org.openpsa.projects
 */
class org_openpsa_projects_task_dba extends midcom_core_dbaobject
{
    const OBTYPE = 6002;

    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_task';

    public $autodelete_dependents = [
        org_openpsa_projects_task_status_dba::class => 'task',
        org_openpsa_projects_task_resource_dba::class => 'task',
    ];

    public $contacts = []; //Shorthand access for contact members
    public $resources = []; // --''--
    public $_skip_acl_refresh = false;
    public $_skip_parent_refresh = false;
    private $_status;

    /**
     * Deny midgard:read by default
     */
    public function get_class_magic_default_privileges()
    {
        $privileges = parent::get_class_magic_default_privileges();
        $privileges['EVERYONE']['midgard:read'] = MIDCOM_PRIVILEGE_DENY;
        return $privileges;
    }

    public function _on_creating()
    {
        $this->orgOpenpsaObtype = self::OBTYPE;
        if (!$this->manager) {
            $this->manager = midcom_connection::get_user();
        }
        return $this->_prepare_save();
    }

    public function _on_loaded()
    {
        if ($this->title == "") {
            $this->title = "Task #{$this->id}";
        }

        if (!$this->status) {
            //Default to proposed if no status is set
            $this->status = org_openpsa_projects_task_status_dba::PROPOSED;
        }
    }

    public function refresh() : bool
    {
        $this->contacts = [];
        $this->resources = [];
        $this->_status = null;
        return parent::refresh();
    }

    public function __get($property)
    {
        if ($property == 'status_type') {
            return org_openpsa_projects_workflow::get_status_type($this->status);
        }
        if (in_array($property, ['status_comment', 'status_time'])) {
            if ($this->_status === null) {
                $this->_status = $this->_get_status();
            }
            return $this->_status[$property];
        }
        return parent::__get($property);
    }

    public function _on_updating()
    {
        return $this->_prepare_save();
    }

    public function _on_updated()
    {
        // Sync the object's ACL properties into MidCOM ACL system
        if (   !$this->_skip_acl_refresh) {
            if ($this->orgOpenpsaAccesstype && $this->orgOpenpsaOwnerWg) {
                debug_add("Synchronizing task ACLs to MidCOM");
                $sync = new org_openpsa_core_acl_synchronizer();
                $sync->write_acls($this, $this->orgOpenpsaOwnerWg, $this->orgOpenpsaAccesstype);
            }

            //Ensure manager can do stuff
            if ($this->manager) {
                $manager_person = midcom::get()->auth->get_user($this->manager);
                $this->set_privilege('midgard:read', $manager_person->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->set_privilege('midgard:create', $manager_person->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->set_privilege('midgard:delete', $manager_person->id, MIDCOM_PRIVILEGE_ALLOW);
                $this->set_privilege('midgard:update', $manager_person->id, MIDCOM_PRIVILEGE_ALLOW);
            }
        }

        $this->_update_parent();
    }

    public function _on_deleting()
    {
        $this->update_cache(false);
        return parent::_on_deleting();
    }

    /**
     * Generate a user-readable label for the task using the task/project hierarchy
     */
    public function get_label()
    {
        $label_elements = [$this->title];
        $task = $this;
        while ($task = $task->get_parent()) {
            if (isset($task->title)) {
                $label_elements[] = $task->title;
            }
        }

        $label = implode(' / ', array_reverse($label_elements));
        return trim($label);
    }

    public function get_icon()
    {
        return 'calendar-check-o';
    }

    /**
     * Populates contacts as resources lists
     */
    public function get_members() : bool
    {
        if (!$this->id) {
            return false;
        }

        $mc = org_openpsa_projects_task_resource_dba::new_collector('task', $this->id);
        $ret = $mc->get_rows(['orgOpenpsaObtype', 'person']);

        foreach ($ret as $data) {
            if ($data['orgOpenpsaObtype'] == org_openpsa_projects_task_resource_dba::CONTACT) {
                $this->contacts[$data['person']] = true;
            } else {
                $this->resources[$data['person']] = true;
            }
        }
        return true;
    }

    /**
     * Adds new contacts or resources
     *
     * @param string $property Where should they be added
     * @param array $ids The IDs of the contacts to add
     */
    public function add_members($property, $ids)
    {
        if (!is_array($ids) || empty($ids)) {
            return;
        }
        if ($property === 'contacts') {
            $type = org_openpsa_projects_task_resource_dba::CONTACT;
        } elseif ($property === 'resources') {
            $type = org_openpsa_projects_task_resource_dba::RESOURCE;
        } else {
            return;
        }

        foreach ($ids as $id) {
            $resource = new org_openpsa_projects_task_resource_dba();
            $resource->orgOpenpsaObtype = $type;
            $resource->task = $this->id;
            $resource->person = (int) $id;
            if ($resource->create()) {
                $this->{$property}[$id] = true;
            }
        }
    }

    private function _prepare_save() : bool
    {
        //Make sure we have end
        if (!$this->end || $this->end == -1) {
            $this->end = time();
        }
        //Make sure we have start
        if (!$this->start) {
            $this->start = min(time(), $this->end - 1);
        }

        // Reset start and end to start/end of day
        $this->start = strtotime('today', $this->start);
        $this->end = strtotime('tomorrow', $this->end) - 1;

        if ($this->start > $this->end) {
            debug_add("start ({$this->start}) is greater than end ({$this->end}), aborting", MIDCOM_LOG_ERROR);
            return false;
        }

        if ($agreement = $this->get_agreement()) {
            // Get customer company into cache from agreement's sales project
            try {
                $agreement = org_openpsa_sales_salesproject_deliverable_dba::get_cached($agreement);
                $this->hoursInvoiceableDefault = true;
                if (!$this->customer) {
                    $salesproject = org_openpsa_sales_salesproject_dba::get_cached($agreement->salesproject);
                    $this->customer = $salesproject->customer;
                }
            } catch (midcom_error $e) {
            }
        } else {
            // No agreement, we can't be invoiceable
            $this->hoursInvoiceableDefault = false;
        }

        // Update hour caches
        $this->update_cache(false);

        return true;
    }

    public function get_agreement() : int
    {
        if ($this->up) {
            do {
                $parent = $this->get_parent();
            } while ($parent->up);
            return $parent->agreement;
        }
        return $this->agreement;
    }

    /**
     * Update hour report caches
     */
    public function update_cache($update = true) : bool
    {
        if (!$this->id) {
            return false;
        }

        debug_add("updating hour caches");

        $hours = $this->list_hours();
        $stat = true;

        $this->reportedHours = $hours['reported'];
        $this->invoicedHours = $hours['invoiced'];
        $this->invoiceableHours = $hours['invoiceable'];

        try {
            $agreement = new org_openpsa_sales_salesproject_deliverable_dba($this->get_agreement());
            $agreement->update_units($this->id, $hours);
        } catch (midcom_error $e) {
        }

        if ($update) {
            $this->_use_rcs = false;
            $this->_skip_acl_refresh = true;
            $this->_skip_parent_refresh = true;
            $stat = $this->update();
            debug_add("saving updated values to database returned {$stat}");
        }
        return $stat;
    }

    private function list_hours() : array
    {
        $hours = [
            'reported'    => 0,
            'invoiced'    => 0,
            'invoiceable' => 0,
        ];

        $report_mc = org_openpsa_expenses_hour_report_dba::new_collector('task', $this->id);
        $report_mc->add_value_property('hours');
        $report_mc->add_value_property('invoice');
        $report_mc->add_value_property('invoiceable');
        $report_mc->execute();

        foreach ($report_mc->list_keys() as $guid => $empty) {
            $report_data = $report_mc->get($guid);
            $report_hours = $report_data['hours'];

            $hours['reported'] += $report_hours;

            if ($report_data['invoiceable']) {
                if ($report_data['invoice']) {
                    $hours['invoiced'] += $report_hours;
                } else {
                    $hours['invoiceable'] += $report_hours;
                }
            }
        }
        return $hours;
    }

    private function _update_parent() : bool
    {
        if (!$this->_skip_parent_refresh) {
            $project = new org_openpsa_projects_project($this->project);
            $project->refresh_from_tasks();
        }

        return true;
    }

    /**
     * Queries status objects and sets correct value to $this->status
     */
    private function _get_status() : array
    {
        $return = [
            'status_comment' => '',
            'status_time' => false,
        ];
        //Simplistic approach
        $mc = org_openpsa_projects_task_status_dba::new_collector('task', $this->id);
        if ($this->status > org_openpsa_projects_task_status_dba::PROPOSED) {
            //Only get proposed status objects here if are not over that phase
            $mc->add_constraint('type', '<>', org_openpsa_projects_task_status_dba::PROPOSED);
        }
        if (!empty($this->resources)) {
            //Do not ever set status to declined if we still have resources left
            $mc->add_constraint('type', '<>', org_openpsa_projects_task_status_dba::DECLINED);
        }
        $mc->add_order('timestamp', 'DESC');
        $mc->add_order('type', 'DESC'); //Our timestamps are not accurate enough so if we have multiple with same timestamp suppose highest type is latest
        $mc->set_limit(1);

        $ret = $mc->get_rows(['type', 'comment', 'timestamp']);
        if (empty($ret)) {
            //Failure to get status object

            //Default to last status if available
            debug_add('Could not find any status objects, defaulting to previous status');
            return $return;
        }
        $status = current($ret);

        //Update the status cache if necessary
        if ($this->status != $status['type']) {
            $this->status = $status['type'];
            $this->update();
        }

        //TODO: Check various combinations of accept/decline etc etc
        $return['status_comment'] = $status['comment'];
        $return['status_time'] = $status['timestamp'];

        return $return;
    }

    public function refresh_status()
    {
        $this->_get_status();
    }
}
