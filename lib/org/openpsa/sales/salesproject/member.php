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

    public function _on_created()
    {
        // Check if the salesman and the contact are buddies already
        $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
        $owner = new midcom_db_person($salesproject->owner);
        $person = new midcom_db_person($this->person);

        $qb = org_openpsa_contacts_buddy_dba::new_query_builder();
        $qb->add_constraint('account', '=', (string)$owner->guid);
        $qb->add_constraint('buddy', '=', (string)$person->guid);
        $qb->add_constraint('blacklisted', '=', false);
        $buddies = $qb->execute();

        if (count($buddies) == 0)
        {
            // Cache the association to buddy list of the sales project owner
            $buddy = new org_openpsa_contacts_buddy_dba();
            $buddy->account = $owner->guid;
            $buddy->buddy = $person->guid;
            $buddy->isapproved = false;
            $buddy->create();
        }
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