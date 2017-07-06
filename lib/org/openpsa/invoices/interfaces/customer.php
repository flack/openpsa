<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for identifying DBA entities which can have both person & organization links
 *
 * @package org.openpsa.invoices
 */
interface org_openpsa_invoices_interfaces_customer
{
    /**
     * @return org_openpsa_contacts_person_dba|org_openpsa_contacts_group_dba
     */
    public function get_customer();
}
