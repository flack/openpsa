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
        if (count($billingdata) > 0)
        {
            return array_pop($billingdata);
        }

        // got no billingdata so far.. auto-create!
        // before autocreation, check if person exists
        try
        {
            $person = new org_openpsa_contacts_person_dba($linkGuid);
        }
        catch (midcom_error $e)
        {
            $this->_stop("Failed to autocreate billingdata. Invalid linkGuid: " . $e->getMessage());
        }

        $billingdata = new org_openpsa_invoices_billing_data_dba();
        $billingdata->linkGuid = $person->guid;
        if (!$billingdata->create())
        {
            return false;
        }

        return $billingdata;
    }

    public function handle_get()
    {
        $filter = $this->_request['params'];

        // just guid
        if (count($filter) == 1)
        {
            return parent::handle_get();
        }
        // got filter
        $linkGuid = isset($filter['linkGuid']) ? $filter['linkGuid'] : false;
        if (!$linkGuid)
        {
            $this->_stop("Invalid filter options");
        }

        $billingdata = $this->get_billingdata($linkGuid);
        if (!$billingdata)
        {
            $this->_stop("Failed to retrieve billingdata, last error was: " . midcom_connection::get_error_string());
        }

        $this->_object = $billingdata;
        $this->_responseStatus = MIDCOM_ERROK;
        $this->_response["object"] = $this->_object;
        $this->_response["message"] = "get ok";
    }
}
