<?php
/**
 * @package org.openpsa.sales
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class, keep logic here
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_salesproject_member_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_salesproject_member';

    public function __construct($id = null)
    {
        $this->_use_rcs = false;
        parent::__construct($id);
    }

    /**
     * Human-readable label for cases like Asgard navigation
     */
    function get_label()
    {
        if ($this->person)
        {
            try
            {
                $person = org_openpsa_contacts_person_dba::get_cached($this->person);
                return $person->name;
            }
            catch (midcom_error $e)
            {
                return "member #{$this->id}";
            }

        }
        return "member #{$this->id}";
    }


    public function _on_creating()
    {
        if (!$this->orgOpenpsaObtype)
        {
            $this->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_SALESPROJECT_MEMBER;
        }
        return true;
    }

    function get_parent_guid_uncached()
    {
        if ($this->salesproject != 0)
        {
            $parent = new org_openpsa_sales_salesproject_dba($this->salesproject);
            return $parent->guid;
        }
        else
        {
            return null;
        }
    }
}
?>