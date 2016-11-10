<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Billing data DBA class
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_billing_data_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_billing_data';

    public function get_parent_guid_uncached()
    {
        return $this->linkGuid;
    }

    public function _on_creating()
    {
        $mc = self::new_collector('linkGuid', $this->linkGuid);
        if ($mc->count() > 0) {
            midcom_connection::set_error(MGD_ERR_DUPLICATE);
            return false;
        }

        return true;
    }

    /**
     * Render the address
     */
    public function render_address()
    {
        //add contact address if needed
        $this->set_address();

        //html-ouptut
        echo '<div class="vcard">';
        echo '<div style="text-align:center"><em>' . midcom::get()->i18n->get_string('invoice address', 'org.openpsa.contacts') . "</em></div>\n";
        echo "<strong>\n";
        echo nl2br($this->recipient) . "\n";
        echo "</strong>\n";
        echo "<p>{$this->street}<br />\n";
        echo "{$this->postcode} {$this->city}</p>\n";
        echo "</div>\n";
    }

    public function get_label()
    {
        $label = midcom::get()->i18n->get_l10n('org.openpsa.invoices')->get('billing data') . ' (';
        if ($contact = $this->get_contact()) {
            $label .= $contact->get_label() . ')';
        } else {
            $label .= $this->linkGuid . ')';
        }
        return $label;
    }

    /**
     * get the contact object
     *
     * @return mixed The contact object or false
     */
    public function get_contact()
    {
        try {
            return new org_openpsa_contacts_person_dba($this->linkGuid);
        } catch (midcom_error $e) {
            try {
                return new org_openpsa_contacts_group_dba($this->linkGuid);
            } catch (midcom_error $e) {
                debug_add("Failed to load contact with GUID: " . $this->linkGuid . " - last error:" . $e->getMessage(), MIDCOM_LOG_ERROR);
                return false;
            }
        }
    }

    /**
     * Function to add the address of the contact(person/group) to the billing_data
     * if the flag useContactAddress is set
     */
    public function set_address()
    {
        if ($this->useContactAddress && !empty($this->linkGuid)) {
            $contact = $this->get_contact();
            switch (true) {
                case is_a($contact, 'org_openpsa_contacts_person_dba'):
                    $this->recipient = $contact->firstname . " " . $contact->lastname;
                    break;
                case is_a($contact, 'org_openpsa_contacts_group_dba'):
                    $this->recipient = $contact->official;
                    break;
                default:
                    break;
            }
            $this->street = $contact->street;
            $this->postcode = $contact->postcode;
            $this->city = $contact->city;
            $this->country = $contact->country;
            $this->email = $contact->email;
        }
    }
}
