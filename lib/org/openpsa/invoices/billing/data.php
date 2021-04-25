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
 * @property string $recipient
 * @property string $street
 * @property string $postcode
 * @property string $city
 * @property string $country
 * @property string $email
 * @property string $accountNumber
 * @property string $bankCode
 * @property string $iban
 * @property string $bic
 * @property string $bankName
 * @property string $vatNo
 * @property string $taxId
 * @property integer $vat
 * @property string $delivery
 * @property integer $due
 * @property integer $sendingoption
 * @property string $remarks
 * @property boolean $useContactAddress
 * @property string $linkGuid
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_billing_data_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_billing_data';

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
     * Get the billing data for the customer in object or create a default data set of it.
     */
    public static function get_by_object(org_openpsa_invoices_interfaces_customer $object) : self
    {
        if (   !($bd = self::get_billing_data(org_openpsa_contacts_group_dba::class, $object->customer))
               // check if the customerContact is set and has invoice_data
            && !($bd = self::get_billing_data(org_openpsa_contacts_person_dba::class, $object->customerContact))) {
            $bd = new self();
            $due = midcom_baseclasses_components_configuration::get('org.openpsa.invoices', 'config')->get('default_due_days');
            $vat = explode(',', midcom_baseclasses_components_configuration::get('org.openpsa.invoices', 'config')->get('vat_percentages'));

            $bd->vat = (int) $vat[0];
            $bd->due = $due;
        }
        return $bd;
    }

    /**
     * Get the billing data for given contact if any.
     *
     * @param mixed $contact_id
     */
    private static function get_billing_data(string $dba_class, $contact_id) : ?self
    {
        if ($contact_id == 0) {
            return null;
        }
        try {
            $contact = $dba_class::get_cached($contact_id);
            $qb = self::new_query_builder();
            $qb->add_constraint('linkGuid', '=', $contact->guid);
            $billing_data = $qb->execute();
            if (empty($billing_data)) {
                return null;
            }

            // call set_address so the billing_data contains address of the linked contact
            // if the property useContactAddress is set
            $billing_data[0]->set_address();
            return $billing_data[0];
        } catch (midcom_error $e) {
            $e->log();
            return null;
        }
    }

    /**
     * Render the address
     */
    public function render_address()
    {
        //add contact address if needed
        $this->set_address();

        //html-output
        echo '<div class="vcard">';
        echo '<div style="text-align:center"><em>' . midcom::get()->i18n->get_string('invoice address', 'org.openpsa.contacts') . "</em></div>\n";
        echo "<strong>\n";
        echo nl2br($this->recipient) . "\n";
        echo "</strong>\n";
        echo "<p>{$this->street}<br />\n";
        echo "{$this->postcode} {$this->city}</p>\n";
        echo "</div>\n";
    }

    public function get_label() : string
    {
        $label = midcom::get()->i18n->get_string('billing data', 'org.openpsa.invoices') . ' (';
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
            if (is_a($contact, org_openpsa_contacts_person_dba::class)) {
                $this->recipient = $contact->firstname . " " . $contact->lastname;
            } elseif (is_a($contact, org_openpsa_contacts_group_dba::class)) {
                $this->recipient = $contact->official;
            }
            $this->street = $contact->street;
            $this->postcode = $contact->postcode;
            $this->city = $contact->city;
            $this->country = $contact->country;
            $this->email = $contact->email;
        }
    }
}
