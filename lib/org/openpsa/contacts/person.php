<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped access to org_openpsa_person plus some utility methods
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_person_dba extends midcom_db_person
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_person';

    private $_register_prober = false;

    function __construct($identifier = null)
    {
        if ($GLOBALS['midcom_config']['person_class'] != 'midgard_person')
        {
            $this->__mgdschema_class_name__ = $GLOBALS['midcom_config']['person_class'];
        }
        parent::__construct($identifier);
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    /**
     * Retrieve a reference to a person object, uses in-request caching
     *
     * @param string $src GUID of person (ids work but are discouraged)
     * @return org_openpsa_contacts_person_dba reference to device object or false
     */
    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    public function __set($name, $value)
    {
        if (   $name == 'homepage'
            && !empty($value)
            && $value != $this->homepage)
        {
            $this->_url_changed = true;
        }
        parent::__set($name, $value);
    }

    public function render_link()
    {
        $siteconfig = new org_openpsa_core_siteconfig();

        $contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts');
        if ($contacts_url)
        {
            return '<a href="' . $contacts_url . 'person/' . $this->guid . '/">' . $this->get_label() . "</a>\n";
        }
        else
        {
            return $this->get_label();
        }
    }

    public function _on_creating()
    {
        //Make sure we have objType
        if (!$this->orgOpenpsaObtype)
        {
            $this->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_PERSON;
        }
        return true;
    }

    public function _on_updated()
    {
        if ($this->_register_prober)
        {
            $args = array
            (
                'person' => $this->guid,
            );
            $_MIDCOM->load_library('midcom.services.at');
            midcom_services_at_interface::register(time() + 60, 'org.openpsa.contacts', 'check_url', $args);
        }
    }

    public function _on_deleting()
    {
        // FIXME: Call duplicate checker's dependency handling methods
        return true;
    }

    function get_label_property()
    {
        if ($this->rname)
        {
            $property = 'rname';
        }
        else
        {
            $property = 'username';
        }

        return $property;
    }
}
?>