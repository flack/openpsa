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

    public function _on_creating()
    {
        return $this->_prepare_save();
    }

    public function _on_updating()
    {
        return $this->_prepare_save();
    }
}
?>