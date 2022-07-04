<?php
/**
 * @package org.openpsa.expenses
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped access to the MgdSchema class, keep logic here
 *
 * @property integer $task
 * @property integer $person
 * @property integer $invoice
 * @property float $hours
 * @property string $description
 * @property integer $date
 * @property string $reportType
 * @property boolean $invoiceable
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_hour_report_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_hour_report';

    public $_skip_parent_refresh = false;

    private $old_task;

    public function __set($property, $value)
    {
        if ($property == 'task' && $value != $this->task) {
            $this->old_task = $this->task;
        }
        parent::__set($property, $value);
    }

    private function _prepare_save() : bool
    {
        $this->hours = round($this->hours, 2);

        //Make sure date is set
        if (!$this->date) {
            $this->date = time();
        }
        //Make sure person is set
        if (!$this->person) {
            $this->person = midcom_connection::get_user();
        }

        return true;
    }

    public function _on_creating() : bool
    {
        return $this->_prepare_save();
    }

    public function _on_created()
    {
        $this->_update_parent();
    }

    public function _on_updating() : bool
    {
        $this->modify_hours_by_time_slot();
        return $this->_prepare_save();
    }

    public function _on_updated()
    {
        if (!$this->_skip_parent_refresh) {
            $this->_update_parent();
        }
    }

    public function _on_deleted()
    {
        $this->_update_parent(true);
    }

    private function _update_parent(bool $delete = false)
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');
        try {
            $parent = new org_openpsa_projects_task_dba($this->task);
            self::update_cache($parent);
            if (!$delete && $parent->status < org_openpsa_projects_task_status_dba::STARTED) {
                //Add person to resources if necessary
                $parent->get_members();
                if (!array_key_exists($this->person, $parent->resources)) {
                    $parent->add_members('resources', [$this->person]);
                }
                org_openpsa_projects_workflow::start($parent, $this->person);
            }

            if ($this->old_task) {
                self::update_cache(new org_openpsa_projects_task_dba($this->old_task));
            }
        } catch (midcom_error $e) {
            $e->log();
        }

        midcom::get()->auth->drop_sudo();
    }

    /**
     * Checks if hour report is invoiceable and rounds according to the
     * time slot defined by task or config (at minimum, one slot is counted).
     */
    public function modify_hours_by_time_slot()
    {
        if ($this->invoiceable) {
            $task = new org_openpsa_projects_task_dba($this->task);
            $time_slot = (float)$task->get_parameter('org.openpsa.projects.projectbroker', 'minimum_slot');
            if (empty($time_slot)) {
                $time_slot = (float) midcom_baseclasses_components_configuration::get('org.openpsa.projects', 'config')->get('default_minimum_time_slot');
                if (empty($time_slot)) {
                    $time_slot = 1;
                }
            }
            $this->hours = max(1, round($this->hours / $time_slot)) * $time_slot;
        }
    }

    public function get_description() : string
    {
        if (!preg_match("/^[\W]*?$/", $this->description)) {
            return $this->description;
        }
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.expenses');
        return "<em>" . $l10n->get('no description given') . "</em>";
    }

    /**
     * Update hour report caches
     */
    public static function update_cache(org_openpsa_projects_task_dba $task) : bool
    {
        if (!$task->id) {
            return false;
        }

        debug_add("updating hour caches");
        $task->reportedHours = $task->invoicedHours = $task->invoiceableHours = 0;

        $report_mc = self::new_collector('task', $task->id);

        foreach ($report_mc->get_rows(['hours', 'invoice', 'invoiceable']) as $report_data) {
            $report_hours = $report_data['hours'];

            $task->reportedHours += $report_hours;

            if ($report_data['invoiceable']) {
                if ($report_data['invoice']) {
                    $task->invoicedHours += $report_hours;
                } else {
                    $task->invoiceableHours += $report_hours;
                }
            }
        }

        $task->_use_rcs = false;
        $task->_skip_acl_refresh = true;
        $task->_skip_parent_refresh = true;
        return $task->update();
    }

    /**
     * Connect task hour reports to an invoice
     */
    public static function mark_invoiced(org_openpsa_projects_task_dba $task, org_openpsa_invoices_invoice_dba $invoice)
    {
        $hours_marked = 0;
        $qb = self::new_query_builder();
        $qb->add_constraint('task', '=', $task->id);
        $qb->add_constraint('invoice', '=', 0);

        foreach ($qb->execute() as $report) {
            $report->invoice = $invoice->id;
            $report->_skip_parent_refresh = true;
            if ($report->update() && $report->invoiceable) {
                $hours_marked += $report->hours;
            }
        }

        // Update hour caches to agreement
        if (!self::update_cache($task)) {
            debug_add('Failed to update task hour caches, last Midgard error: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
        }

        // Notify user
        midcom::get()->uimessages->add(midcom::get()->i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'), sprintf(midcom::get()->i18n->get_string('marked %s hours as invoiced in task "%s"', 'org.openpsa.projects'), $hours_marked, $task->title));
        return $hours_marked;
    }
}
