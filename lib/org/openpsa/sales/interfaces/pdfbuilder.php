<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for pdf builder for sales
 *
 * @package org.openpsa.sales
 */
interface org_openpsa_sales_interfaces_pdfbuilder
{
    /**
     * Constructor
     */
    public function __construct(org_openpsa_sales_salesproject_offer_dba $offer);

    /**
     * Perform the pdf rendering
     *
     * @param string $output_filename the location the pdf gets rendered to
     */
    public function render(string $output_filename);
}
