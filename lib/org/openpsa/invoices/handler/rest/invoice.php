<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 * @author Jan Floegel
 * @package org.openpsa.invoices
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General
 */

/**
 * org.openpsa.invoices rest invoice handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_rest_invoice extends midcom_baseclasses_components_handler_rest
{
    public function get_object_classname()
    {
        return "org_openpsa_invoices_invoice_dba";
    }

    public function handle_get()
    {
        $filter = $this->_request['params'];

        // just guid
        if (count($filter) == 1) {
            return parent::handle_get();
        }

        // got filter
        if (!isset($filter['person_guid'])) {
            throw new midcom_error("Invalid filter options");
        }

        $person = new org_openpsa_contacts_person_dba($filter['person_guid']);

        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_constraint("customerContact", "=", $person->id);
        $invoices = $qb->execute();

        $data = [];
        foreach ($invoices as $invoice) {
            $date = $invoice->date ? $invoice->date : $invoice->metadata->created;
            $data[] = [
                "guid" => $invoice->guid,
                "number" => $invoice->number,
                "date" => $date,
                "status" => $invoice->get_status(),
                "sum" => $invoice->sum
            ];
        }

        $this->_responseStatus = 200;
        $this->_response["data"] = $data;
    }
}
