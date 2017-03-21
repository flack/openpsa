<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for identifying the invoice_dba and salesproject_dba
 *
 * @package org.openpsa.invoice
 */
interface org_openpsa_invoices_interfaces_customer
{
    /**
     * @return org_openpsa_contacts_person_dba|org_openpsa_contacts_group_dba
     */
    public function get_customer();
}
