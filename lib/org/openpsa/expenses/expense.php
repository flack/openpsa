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
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_expense extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_expense';

    private $_locale_backup = '';

    static function new_query_builder()
    {
        return midcom::get('dbfactory')->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return midcom::get('dbfactory')->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return midcom::get('dbfactory')->get_cached(__CLASS__, $src);
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
        //Is task is not set abort
        if (!$this->task)
        {
            return false;
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
    }

    public function _on_updating()
    {
        $this->_locale_set();
        return $this->_prepare_save();
    }

    public function _on_updated()
    {
        $this->_locale_restore();
    }
}
?>