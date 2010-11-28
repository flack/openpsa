<?php
/**
 * @package org.openpsa.relatedto
 * @author Nemein Oy, http://www.nemein.com/
 * @version $Id: dba.php 25217 2010-02-27 18:56:49Z flack $
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class, keep logic here
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_relatedto';

    function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
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

    function _on_creating()
    {
        if (!$this->status)
        {
            $this->status = ORG_OPENPSA_RELATEDTO_STATUS_SUSPECTED;
        }
        //PONDER: Should we call check_db() here and prevent creation of multiple very similar links ??
        return true;
    }

    function _on_loaded()
    {
        if (!$this->status)
        {
            $this->status = ORG_OPENPSA_RELATEDTO_STATUS_SUSPECTED;
        }
        return true;
    }

    function _on_updating()
    {
        if (!$this->status)
        {
            $this->status = ORG_OPENPSA_RELATEDTO_STATUS_SUSPECTED;
        }
        return true;
    }


    /**
     * Check database for essentially same relatedto object and returns id if found
     */
    function check_db($check_status = true)
    {
        $mc = org_openpsa_relatedto_dba::new_collector('toGuid', $this->toGuid);
        $mc->set_key_property('id');
        $mc->add_constraint('fromClass', '=', $this->fromClass);
        $mc->add_constraint('toClass', '=', $this->toClass);
        $mc->add_constraint('fromGuid', '=', $this->fromGuid);
        $mc->add_constraint('fromComponent', '=', $this->fromComponent);
        $mc->add_constraint('toComponent', '=', $this->toComponent);
        if ($check_status)
        {
            $mc->add_constraint('status', '=', $this->status);
        }
        $mc->set_limit(1);
        $mc->execute();
        $ret = $mc->list_keys();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            return array_shift(array_keys($ret));
        }

        return false;
    }

    /**
     * By default all authenticated users should be able to do
     * whatever they wish with relatedto objects, later we can add
     * restrictions on object level as necessary.
     */
    function get_class_magic_default_privileges()
    {
        $privileges = parent::get_class_magic_default_privileges();
        $privileges['USERS']['midgard:create']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:update']  = MIDCOM_PRIVILEGE_ALLOW;
        $privileges['USERS']['midgard:read']    = MIDCOM_PRIVILEGE_ALLOW;
        return $privileges;
    }
}
?>