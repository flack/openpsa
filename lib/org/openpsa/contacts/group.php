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

    public $autodelete_dependents = array(
        'org_openpsa_contacts_member_dba' => 'gid'
    );

    const OTHERGROUP = 0;
    const MYCONTACTS = 500;
    const ORGANIZATION = 1000;
    const DAUGHTER = 1001;
    const DEPARTMENT = 1002;

    private $members = array();
    private $_members_loaded = false;
    private $_register_prober = false;
    private $_address_extras = array();

    public function __set($name, $value)
    {
        if (   $name == 'homepage'
            && !empty($value)
            && $value != $this->homepage) {
            $this->_register_prober = true;
        }
        parent::__set($name, $value);
    }

    public function get_label()
    {
        if ($this->official) {
            return $this->official;
        }
        return $this->name;
    }

    public function get_label_property()
    {
        if ($this->official) {
            return 'official';
        }
        return 'name';
    }

    public function render_link()
    {
        $siteconfig = new org_openpsa_core_siteconfig();

        if ($contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts')) {
            return '<a href="' . $contacts_url . 'group/' . $this->guid . '/">' . $this->get_label() . "</a>\n";
        }
        return $this->get_label();
    }

    private function _get_address_extra($property)
    {
        $return = $this->get_parameter('midcom.helper.datamanager2', $property);
        if (!$return) {
            $return = $this->get_label();
        }

        $this->_address_extras[$property] = $return;
    }

    public function __get($property)
    {
        if (   $property == 'invoice_label'
            || $property == 'postal_label') {
            if (!isset($this->_address_extras[$property])) {
                $this->_get_address_extra($property);
            }
            return $this->_address_extras[$property];
        }
        return parent::__get($property);
    }

    public function _on_loaded()
    {
        if (empty($this->official)) {
            $this->official = $this->name ?: "Group #{$this->id}";
        }
    }

    public function _on_creating()
    {
        //Make sure we have accessType
        if (!$this->orgOpenpsaAccesstype) {
            $this->orgOpenpsaAccesstype = org_openpsa_core_acl::ACCESS_PUBLIC;
        }
        return true;
    }

    public function _on_updated()
    {
        if ($this->_register_prober) {
            $args = array('group' => $this->guid);
            midcom_services_at_interface::register(time() + 60, 'org.openpsa.contacts', 'check_url', $args);
        }
    }

    private function _get_members_array()
    {
        if (!$this->_members_loaded) {
            $mc = midcom_db_member::new_collector('gid', $this->id);
            $this->members = array_fill_keys($mc->get_values('uid'), true);
            $this->_members_loaded = true;
        }
        return $this->members;
    }

    public function get_members()
    {
        return $this->_get_members_array();
    }
}
