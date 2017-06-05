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
    const TYPE_PERSON = 2000;

    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_person';

    public $autodelete_dependents = [
        'org_openpsa_calendar_event_member_dba' => 'uid',
        'midcom_db_member' => 'uid'
    ];

    private $_register_prober = false;

    public function __construct($identifier = null)
    {
        if (   midcom::get()->config->get('person_class') != 'midgard_person'
            && midcom::get()->config->get('person_class') != 'openpsa_person') {
            $this->__mgdschema_class_name__ = midcom::get()->config->get('person_class');
        }
        parent::__construct($identifier);
    }

    public function __set($name, $value)
    {
        if (   $name == 'homepage'
            && !empty($value)
            && $value != $this->homepage) {
            $this->_url_changed = true;
        }
        parent::__set($name, $value);
    }

    public function render_link()
    {
        $siteconfig = new org_openpsa_core_siteconfig();

        if ($contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts')) {
            return '<a href="' . $contacts_url . 'person/' . $this->guid . '/">' . $this->get_label() . "</a>\n";
        }
        return $this->get_label();
    }

    public function _on_creating()
    {
        //Make sure we have objType
        if (!$this->orgOpenpsaObtype) {
            $this->orgOpenpsaObtype = self::TYPE_PERSON;
        }
        return true;
    }

    public function _on_updated()
    {
        if ($this->_register_prober) {
            $args = [
                'person' => $this->guid,
            ];
            midcom_services_at_interface::register(time() + 60, 'org.openpsa.contacts', 'check_url', $args);
        }
    }

    public function _on_deleting()
    {
        // FIXME: Call duplicate checker's dependency handling methods
        return true;
    }

    public function get_label_property()
    {
        return 'rname';
    }
}
