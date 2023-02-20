<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @property string $name Path name of the group
 * @property string $official Official name of the group
 * @property string $street Street address of the group
 * @property string $postcode Zip code of the group
 * @property string $city City of the group
 * @property string $country Country of the group
 * @property string $homepage Homepage URL of the group
 * @property string $email Email of the group
 * @property string $phone Phone number of the group
 * @property string $fax Fax number of the group
 * @property string $extra Additional information about the group
 * @property integer $owner Group the group is under
 * @property integer $orgOpenpsaObtype
 * @property string $postalStreet
 * @property string $postalPostcode
 * @property string $postalCity
 * @property string $postalCountry
 * @property string $invoiceStreet
 * @property string $invoicePostcode
 * @property string $invoiceCity
 * @property string $invoiceCountry
 * @property string $keywords
 * @property integer $invoiceDue
 * @property integer $invoiceVat
 * @property string $invoiceDistribution
 * @property string $vatNo
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_group_dba extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_organization';

    public array $autodelete_dependents = [
        org_openpsa_contacts_member_dba::class => 'gid'
    ];

    const OTHERGROUP = 0;
    const ORGANIZATION = 1000;
    const DAUGHTER = 1001;
    const DEPARTMENT = 1002;

    private $members = [];
    private $_members_loaded = false;
    private $_register_prober = false;
    private $_address_extras = [];

    public function __set($name, $value)
    {
        if (   $name == 'homepage'
            && !empty($value)
            && $value != $this->homepage) {
            $this->_register_prober = true;
        }
        parent::__set($name, $value);
    }

    public function get_label() : string
    {
        return $this->official ?: $this->name;
    }

    public function render_link() : string
    {
        $siteconfig = new org_openpsa_core_siteconfig();

        if ($contacts_url = $siteconfig->get_node_full_url('org.openpsa.contacts')) {
            return '<a href="' . $contacts_url . 'group/' . $this->guid . '/">' . $this->get_label() . "</a>";
        }
        return $this->get_label();
    }

    private function _get_address_extra(string $property)
    {
        $return = $this->get_parameter('midcom.helper.datamanager2', $property) ?: $this->get_label();
        $this->_address_extras[$property] = $return;
    }

    public function __get($property)
    {
        if (in_array($property, ['invoice_label', 'postal_label'])) {
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

    public function _on_updated()
    {
        if ($this->_register_prober) {
            $args = ['group' => $this->guid];
            midcom_services_at_interface::register(time() + 60, 'org.openpsa.contacts', 'check_url', $args);
        }
    }

    public function get_members() : array
    {
        if (!$this->_members_loaded) {
            $mc = midcom_db_member::new_collector('gid', $this->id);
            $this->members = array_fill_keys($mc->get_values('uid'), true);
            $this->_members_loaded = true;
        }
        return $this->members;
    }
}
