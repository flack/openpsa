<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for pdf builder for invoices
 *
 * @package org.openpsa.invoices
 */
interface org_openpsa_invoices_interfaces_pdfbuilder
{
    /**
     * Constructor
     */
    public function __construct(org_openpsa_invoices_invoice_dba $invoice);

    /**
     * Perform the pdf rendering
     *
     * @param string $output_filename the location the pdf gets rendered to
     */
    public function render(string $output_filename);
}
