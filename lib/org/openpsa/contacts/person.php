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
 * @property string $firstname First name of the person
 * @property string $lastname Last name of the person
 * @property string $homephone Home phone number of the person
 * @property string $handphone Cell phone number of the person
 * @property string $workphone Work phone name of the person
 * @property string $homepage Homepage URL of the person
 * @property string $email Email address of the person
 * @property string $street Street address of the person
 * @property string $postcode Zip code of the person
 * @property string $city City of the person
 * @property string $extra Additional information about the person
 * @property integer $salutation
 * @property string $title
 * @property midgard_datetime $birthdate
 * @property string $pgpkey
 * @property string $country
 * @property string $fax
 * @property integer $orgOpenpsaAccesstype
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_person_dba extends midcom_db_person
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_person';

    public $autodelete_dependents = [
        org_openpsa_calendar_event_member_dba::class => 'uid',
        midcom_db_member::class => 'uid'
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
            $this->_register_prober = true;
        }
        parent::__set($name, $value);
    }

    public function render_link()
    {
        $siteconfig = new org_openpsa_core_siteconfig();

        if ($contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts')) {
            return '<a href="' . $contacts_url . 'person/' . $this->guid . '/">' . $this->get_label() . "</a>";
        }
        return $this->get_label();
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
        return parent::_on_deleting();
    }

    public function get_label_property()
    {
        return 'rname';
    }
}
