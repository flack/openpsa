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
class org_openpsa_projects_hour_report_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_hour_report';

    private $_locale_backup = '';
    var $_skip_parent_refresh = false;

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

    function get_parent_guid_uncached()
    {
        if ($this->task != 0)
        {
            $parent = new org_openpsa_projects_task_dba($this->task);
            return $parent->guid;
        }
        else
        {
            return null;
        }
    }

    private function _prepare_save()
    {
        //Make sure our hours property is a float
        $this->hours = (float) $this->hours;
        $this->hours = round($this->hours, 2);

        //Make sure date is set
        if (!$this->date)
        {
            $this->date = time();
        }
        //Make sure person is set
        if (!$this->person)
        {
            $this->person = midcom_connection::get_user();
        }

        return true;
    }

    private function _locale_set()
    {
        $this->_locale_backup = setlocale(LC_NUMERIC, '0');
        setlocale(LC_NUMERIC, 'C');
    }

    private function _locale_restore()
    {
        setlocale(LC_NUMERIC, $this->_locale_backup);
    }

    public function _on_creating()
    {
        $this->_locale_set();
        return $this->_prepare_save();
    }

    public function _on_created()
    {
        $this->_locale_restore();
        //Try to mark the parent task as started
        $parent = new org_openpsa_projects_task_dba($this->task);
        if ($parent->guid)
        {
            $parent->update_cache();
            org_openpsa_projects_workflow::start($parent, $this->person);
        }
        return true;
    }

    public function _on_updating()
    {
        $this->_locale_set();
        $this->modify_hours_by_time_slot(false);
        return $this->_prepare_save();
    }

    public function _on_updated()
    {
        $this->_locale_restore();
        if ($this->_skip_parent_refresh)
        {
            return true;
        }
        $parent = new org_openpsa_projects_task_dba($this->task);
        if ($parent->guid)
        {
            $parent->update_cache();
        }

        return true;
    }

    public function _on_deleted()
    {
        $parent = new org_openpsa_projects_task_dba($this->task);
        if ($parent->guid)
        {
            $parent->update_cache();
        }
    }

    /**
     * function checks if hour_report is invoiceable & applies minimum time slot
     */
    function modify_hours_by_time_slot($update = true)
    {
        if($this->invoiceable)
        {
            $task = new org_openpsa_projects_task_dba($this->task);
            $time_slot = (float)$task->get_parameter('org.openpsa.projects.projectbroker', 'minimum_slot');
            if(empty($time_slot) || $time_slot == 0)
            {
                $time_slot = (float) midcom_baseclasses_components_configuration::get('org.openpsa.projects', 'config')->get('default_minimum_time_slot');
                if(empty($time_slot) || $time_slot == 0)
                {
                    $time_slot = 1;
                }
            }
            $time_slot_amount = $this->hours / $time_slot;
            $time_slot_amount_int = intval($time_slot_amount);
            $difference =  $time_slot_amount - (float)$time_slot_amount_int;
            if($difference > 0.5 || $time_slot_amount_int == 0)
            {
                $time_slot_amount_int++;
            }
            $this->hours = $time_slot_amount_int * $time_slot;
            if($update)
            {
                $this->update();
            }
        }
    }
}
?>