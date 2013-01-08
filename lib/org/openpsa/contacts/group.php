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
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_organization';

    const OTHERGROUP = 0;
    const MYCONTACTS = 500;
    const ORGANIZATION = 1000;
    const DAUGHTER = 1001;
    const DEPARTMENT = 1002;

    var $members = array();
    private $_members_loaded = false;
    private $_register_prober = false;
    private $_address_extras = array();

    public function __set($name, $value)
    {
        if (   $name == 'homepage'
            && !empty($value)
            && $value != $this->homepage)
        {
            $this->_register_prober = true;
        }
        parent::__set($name, $value);
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

    public function render_link()
    {
        $siteconfig = new org_openpsa_core_siteconfig();

        $contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts');
        if ($contacts_url)
        {
            return '<a href="' . $contacts_url . 'group/' . $this->guid . '/">' . $this->get_label() . "</a>\n";
        }
        else
        {
            return $this->get_label();
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

    public function __get($property)
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

    public function _on_loaded()
    {
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
    }

    public function _on_creating()
    {
        //Make sure we have accessType
        if (!$this->orgOpenpsaAccesstype)
        {
            $this->orgOpenpsaAccesstype = org_openpsa_core_acl::ACCESS_PUBLIC;
        }
        return true;
    }

    public function _on_updated()
    {
        if ($this->_register_prober)
        {
            $args = array
            (
                'group' => $this->guid,
            );
            midcom_services_at_interface::register(time() + 60, 'org.openpsa.contacts', 'check_url', $args);
        }
    }

    private function _get_members_array()
    {
        if ($this->_members_loaded)
        {
            return $this->members;
        }
        $this->members = array();
        $mc = midcom_db_member::new_collector('gid', $this->id);
        $uids = $mc->get_values('uid');

        foreach ($uids as $uid)
        {
            $this->members[$uid] = true;
        }
        $this->_members_loaded = true;
        return $this->members;
    }

    public function get_members()
    {
        return $this->_get_members_array();
    }
}
?>