<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @property integer $id Local non-replication-safe database identifier
 * @property integer $up  In practice all salesprojects will have up of 0 but in case we someday wish to divide a huge salesproject to subprojects this is here
 * @property integer $start
 * @property integer $end
 * @property string $code
 * @property string $title
 * @property string $description
 * @property integer $state
 * @property integer $customer
 * @property integer $customerContact
 * @property integer $owner
 * @property integer $probability
 * @property float $value
 * @property float $profit
 * @property float $price
 * @property float $cost
 * @property integer $closeEst
 * @property string $guid
 * @property integer $status Current project status
 * @property float $plannedHours
 * @property float $reportedHours
 * @property float $invoicedHours
 * @property float $invoiceableHours
 * @property integer $orgOpenpsaAccesstype Shortcut for various ACL scenarios
 * @property string $orgOpenpsaOwnerWg The "owner" workgroup of this object
 * @package org.openpsa.projects
 */
class org_openpsa_projects_project extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_project';

    public $autodelete_dependents = [
        org_openpsa_contacts_role_dba::class => 'objectGuid'
    ];

    public $contacts = []; //Shorthand access for contact members
    public $resources = []; // --''--

    /**
     * Map that defines project status changes based on what types of tasks are available
     *
     * First-level key is the project's own status type
     * Beginning from second level, keys are parsed from top to bottom, and the first match is used
     * Null values mean no change
     *
     * @var array
     */
    private $_status_map = [
        'rejected' => [
            'ongoing' => org_openpsa_projects_task_status_dba::REOPENED, // If there's an ongoing task, the project seems to have resumed
            'not_started' => org_openpsa_projects_task_status_dba::PROPOSED, // There are pending tasks, so maybe the project is back to start
            'rejected' => null, // Sanity check
            'on_hold' => org_openpsa_projects_task_status_dba::ONHOLD, //Blocker task
            'closed' => org_openpsa_projects_task_status_dba::COMPLETED //Work seems to have been finished
        ],
        'not_started' => [
            'ongoing' => org_openpsa_projects_task_status_dba::STARTED, //Work seems to have been started
            'on_hold' => org_openpsa_projects_task_status_dba::ONHOLD, // Or is on hold
            'not_started' => null,
            'closed' => org_openpsa_projects_task_status_dba::COMPLETED // Or is even finished already
        ],
        'ongoing' => [
            'ongoing' => null, //Only do something if there are no ongoing tasks
            'on_hold' => org_openpsa_projects_task_status_dba::ONHOLD, //Blocker task
            'not_started' => [
                'closed' => org_openpsa_projects_task_status_dba::ONHOLD, //Project is in limbo: Some tasks are finished, others didn't begin yet
                'not_started' => org_openpsa_projects_task_status_dba::PROPOSED //Back to start: Someone withdrew acceptance
            ],
            'closed' => org_openpsa_projects_task_status_dba::ONHOLD //Work seems to have been finished
        ],
        'closed' => [
            'not_started' => org_openpsa_projects_task_status_dba::REOPENED, //Something new came up, reopen
            'ongoing' => org_openpsa_projects_task_status_dba::REOPENED, //Something new came up, reopen
            'closed' => null, //Sanity check
            'on_hold' => org_openpsa_projects_task_status_dba::ONHOLD
        ],
        'on_hold' => [
            'on_hold' => null, //only if no task is on hold we have to look for something else
            'not_started' => [
                'closed' => null,
                'not_started' => org_openpsa_projects_task_status_dba::PROPOSED // If nothing is closed, ongoing or on hold, let's try not_started
            ],
            'closed' => org_openpsa_projects_task_status_dba::COMPLETED // If nothing is not_started, ongoing or on hold, let's try closed
        ]
    ];

    public function __get($property)
    {
        if ($property == 'status_type') {
            return org_openpsa_projects_workflow::get_status_type($this->status);
        }
        return parent::__get($property);
    }

    public function _on_loaded()
    {
        if ($this->title == "") {
            $this->title = "Project #{$this->id}";
        }

        if (!$this->status) {
            //Default to proposed if no status is set
            $this->status = org_openpsa_projects_task_status_dba::PROPOSED;
        }
    }

    public function get_icon() : string
    {
        return 'tasks';
    }

    /**
     * Generate a user-readable label for the task using the task/project hierarchy
     */
    public function get_label() : string
    {
        $label_elements = [$this->title];
        $project = $this;
        while ($project = $project->get_parent()) {
            if (!empty($project->title)) {
                $label_elements[] = $project->title;
            }
        }

        $label = implode(' / ', array_reverse($label_elements));
        return trim($label);
    }

    public function get_salesproject() : org_openpsa_sales_salesproject_dba
    {
        return new org_openpsa_sales_salesproject_dba($this->id);
    }

    /**
     * Populates contacts as resources lists
     */
    public function get_members()
    {
        if ($this->guid) {
            $mc = org_openpsa_contacts_role_dba::new_collector('objectGuid', $this->guid);
            $ret = $mc->get_rows(['role', 'person']);

            foreach ($ret as $data) {
                if ($data['role'] == org_openpsa_projects_task_resource_dba::CONTACT) {
                    $this->contacts[$data['person']] = true;
                } else {
                    $this->resources[$data['person']] = true;
                }
            }
        }
    }

    /**
     * Get the number of tasks for the different status types
     */
    public function get_task_count() : array
    {
        $numbers = [
            'not_started' => 0,
            'ongoing' => 0,
            'on_hold' => 0,
            'closed' => 0,
            'rejected' => 0
        ];
        $task_mc = org_openpsa_projects_task_dba::new_collector('project', $this->id);
        $statuses = $task_mc->get_values('status');
        foreach ($statuses as $status) {
            $type = org_openpsa_projects_workflow::get_status_type($status);
            $numbers[$type]++;
        }
        return $numbers;
    }

    /**
     * Get the number of hours for the different status types
     */
    public function get_task_hours() : array
    {
        $numbers = [
            'plannedHours' => 0,
            'reportedHours' => 0
        ];
        $task_mc = org_openpsa_projects_task_dba::new_collector('project', $this->id);
        $tasks = $task_mc->get_rows(['plannedHours', 'reportedHours']);
        foreach ($tasks as $values) {
            $numbers['plannedHours'] += $values['plannedHours'];
            $numbers['reportedHours'] += $values['reportedHours'];
        }
        return $numbers;
    }

    /**
     * Set project information according to the situation of its tasks
     *
     * This adjusts the timeframe if necessary and tries to determine the project's
     * status according to the current task situation
     */
    public function refresh_from_tasks()
    {
        $update_required = false;

        $task_statuses = [];
        $status_types = [];

        $task_qb = org_openpsa_projects_task_dba::new_query_builder();
        $task_qb->add_constraint('project', '=', $this->id);
        $ret = $task_qb->execute();

        if (empty($ret)) {
            return;
        }

        foreach ($ret as $task) {
            if ($task->start < $this->start) {
                $this->start = $task->start;
                $update_required = true;
            }
            if ($task->end > $this->end) {
                $this->end = $task->end;
                $update_required = true;
            }

            $status_types[$task->status_type] = true;
            $task_statuses[$task->status] = true;
        }

        if (count($task_statuses) == 1) {
            // If all tasks are of the same type, that is the type to use then
            $new_status = key($task_statuses);
        } else {
            $new_status = $this->_find_status($this->_status_map[$this->status_type], $status_types);
        }

        if (   $new_status !== null
            && $this->status != $new_status) {
            $this->status = $new_status;
            $update_required = true;
        }

        if ($update_required) {
            debug_add("Some project information needs to be updated, skipping RCS");
            $this->_use_rcs = false;
            $this->update();
        } else {
            debug_add("All project information is up-to-date");
        }
    }

    private function _find_status(array $map, array $status_types) : ?int
    {
        foreach (array_intersect_key($map, $status_types) as $new_status) {
            if (is_array($new_status)) {
                return $this->_find_status($new_status, $status_types);
            }
            return $new_status;
        }
        return null;
    }
}
