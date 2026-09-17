<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Dummy invoice PDF builder for test runs
 *
 * @package openpsa.test
 */
class mock_pdfbuilder implements org_openpsa_invoices_interfaces_pdfbuilder
{
    public function __construct(org_openpsa_invoices_invoice_dba $invoice)
    {
    }

    public function render(string $output_filename)
    {
        file_put_contents($output_filename, '%PDF-1.4');
    }
}
