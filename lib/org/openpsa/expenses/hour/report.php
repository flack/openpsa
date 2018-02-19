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
 * @property integer $id Local non-replication-safe database identifier
 * @property integer $task
 * @property integer $person
 * @property integer $invoice
 * @property float $hours
 * @property string $description
 * @property integer $date
 * @property string $reportType
 * @property boolean $invoiceable
 * @property integer $orgOpenpsaObtype Used to a) distinguish OpenPSA objects in QB b) store object "subtype" (project vs task etc)
 * @property string $guid
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_hour_report_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_hour_report';

    public $_skip_parent_refresh = false;

    private function _prepare_save()
    {
        //Make sure our hours property is a float
        $this->hours = (float) $this->hours;
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

    public function _on_creating()
    {
        return $this->_prepare_save();
    }

    public function _on_created()
    {
        $this->_update_parent(true);
    }

    public function _on_updating()
    {
        $this->modify_hours_by_time_slot(false);
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
        $this->_update_parent();
    }

    private function _update_parent($start = false)
    {
        try {
            $parent = new org_openpsa_projects_task_dba($this->task);
            $parent->update_cache();
        } catch (midcom_error $e) {
            return false;
        }
        if ($start) {
            org_openpsa_projects_workflow::start($parent, $this->person);
            //Add person to resources if necessary
            $parent->get_members();
            if (!array_key_exists($this->person, $parent->resources)) {
                $parent->add_members('resources', [$this->person]);
            }
        }
    }

    /**
     * Checks if hour report is invoiceable and rounds according to the
     * time slot defined by task or config (at minimum, one slot is counted).
     */
    public function modify_hours_by_time_slot($update = true)
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
            if ($update) {
                $this->update();
            }
        }
    }

    public function get_description()
    {
        if (!preg_match("/^[\W]*?$/", $this->description)) {
            return $this->description;
        }
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.expenses');
        return "<em>" . $l10n->get('no description given') . "</em>";
    }
}
