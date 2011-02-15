<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */
/**
 * MidCOM wrapped class for access to stored queries
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_buddy_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'net_nehmer_buddylist_entry_db';

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
        if ($this->account)
        {
            try
            {
                $person = new org_openpsa_contacts_person_dba($this->account);
                return $person->guid;
            }
            catch (midcom_error $e)
            {
                return null;
            }
        }
        else
        {
            // Not saved buddy, return user himself
            return $_MIDCOM->auth->user->get_storage();
        }
        return null;
    }

    /**
     * Creation handler, grants owner permissions to the buddy user for this
     * buddy object, so that he can later approve / reject the request. For
     * safety reasons, the owner privilege towards the account user is also
     * created, so that there is no discrepancy later in case administrators
     * create the object.
     */
    public function _on_created()
    {
        if ($user = $_MIDCOM->auth->get_user($this->buddy))
        {
            $this->set_privilege('midgard:owner', $user);
        }
        if ($user = $_MIDCOM->auth->get_user($this->account))
        {
            $this->set_privilege('midgard:owner', $user);
        }
    }

    /**
     * The pre-creation hook sets the added field to the current timestamp if and only if
     * it is unset.
     */
    public function _on_creating()
    {
        if (! $this->added)
        {
            $this->added = time();
        }
        return true;
    }
}
?>