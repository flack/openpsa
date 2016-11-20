<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package org.openpsa.invoices
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

/**
 * org.openpsa.invoices rest billingdata handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_rest_billingdata extends midcom_baseclasses_components_handler_rest
{
    public function get_object_classname()
    {
        return "org_openpsa_invoices_billing_data_dba";
    }

    private function get_billingdata($linkGuid)
    {
        $qb = org_openpsa_invoices_billing_data_dba::new_query_builder();
        $qb->add_constraint("linkGuid", "=", $linkGuid);
        $billingdata = $qb->execute();
        if (count($billingdata) > 0) {
            return array_pop($billingdata);
        }

        // got no billingdata so far.. auto-create!
        // before autocreation, check if person exists
        $person = new org_openpsa_contacts_person_dba($linkGuid);

        $billingdata = new org_openpsa_invoices_billing_data_dba();
        $billingdata->linkGuid = $person->guid;
        if (!$billingdata->create()) {
            throw new midcom_error("Failed to create billingdata, last error was: " . midcom_connection::get_error_string());
        }

        return $billingdata;
    }

    public function handle_get()
    {
        $filter = $this->_request['params'];

        // just guid
        if (count($filter) == 1) {
            return parent::handle_get();
        }
        // got filter
        if (!isset($filter['linkGuid'])) {
            throw new midcom_error("Invalid filter options");
        }

        $this->_object = $this->get_billingdata($filter['linkGuid']);
        $this->_responseStatus = MIDCOM_ERROK;
        $this->_response["object"] = $this->_object;
        $this->_response["message"] = "get ok";
    }
}
