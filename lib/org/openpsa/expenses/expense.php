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
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_expense';

    private $_locale_backup = '';
    
    function __construct($id = null)
    {
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
        //Make sure date is set
        if (!$this->date)
        {
            $this->date = time();
        }
        //Make sure person is set
        if (!$this->person)
        {
            $this->person = $_MIDGARD['user'];
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

    function _on_creating()
    {
        $this->_locale_set();
        return $this->_prepare_save();
    }

    function _on_created()
    {
        $this->_locale_restore();
    }

    function _on_updating()
    {
        $this->_locale_set();
        return $this->_prepare_save();
    }

    function _on_updated()
    {
        $this->_locale_restore();
    }
}

?>