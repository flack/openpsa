<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_group_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_organization';

    var $members = array();
    var $members_loaded = false;

    private $_address_extras = array();

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

    function get_label()
    {
        if ($this->official)
        {
            $label = $this->official;
        }
        else
        {
            $label = $this->name;
        }

        return $label;
    }

    function get_label_property()
    {
        if ($this->official)
        {
            $property = 'official';
        }
        else
        {
            $property = 'name';
        }

        return $property;
    }

    function get_parent_guid_uncached()
    {
        // FIXME: Midgard Core should do this
        if ($this->owner != 0)
        {
            $parent = new org_openpsa_contacts_group_dba($this->owner);
            return $parent->guid;
        }
        else
        {
            return null;
        }
    }

    private function _get_address_extra($property)
    {
        $return = $this->get_parameter('midcom.helper.datamanager2', $property);
        if (!$return)
        {
            $return = $this->get_label();
        }

        $this->_address_extras[$property] = $return;
    }

    function __get($property)
    {
        if ($property == 'invoice_label'
            || $property == 'postal_label')
        {
            if (!isset($this->_address_extras[$property]))
            {
                $this->_get_address_extra($property);
            }
            return $this->_address_extras[$property];
        }
        return parent::__get($property);
    }

    function _on_loaded()
    {
        if (   array_key_exists('org_openpsa_contacts_group_autoload_members', $GLOBALS)
            && $GLOBALS['org_openpsa_contacts_group_autoload_members'])
        {
            $this->_get_members();
        }

        if (empty($this->official))
        {
            if (!empty($this->name))
            {
                $this->official = $this->name;
            }
            else
            {
                $this->official = "Group #{$this->id}";
            }
        }

        return parent::_on_loaded();
    }

    function _on_creating()
    {
        //Make sure we have accessType
        if (!$this->orgOpenpsaAccesstype)
        {
            $this->orgOpenpsaAccesstype = ORG_OPENPSA_ACCESSTYPE_PUBLIC;
        }
        return parent::_on_creating();
    }

    function _on_updating()
    {
        if ($this->homepage)
        {
            // This group has a homepage, register a prober
            $args = array
            (
                'group' => $this->guid,
            );
            $_MIDCOM->load_library('midcom.services.at');
            midcom_services_at_interface::register(time() + 60, 'org.openpsa.contacts', 'check_url', $args);
        }

        return parent::_on_updating();
    }

    function _get_members_array()
    {
        $members = array();
        $mc = midcom_db_member::new_collector('gid', $this->id);
        $mc->add_value_property('uid');
        $mc->execute();
        $ret = $mc->list_keys();
        if (count($ret) > 0)
        {
            foreach ($ret as $guid => $empty)
            {
                $members[$mc->get_subkey($guid, 'uid')] = true;
            }
        }
        return $members;
    }

    function _get_members()
    {
        $this->members = $this->_get_members_array();
        $this->members_loaded = true;
    }
}
?>