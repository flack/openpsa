<?php
/**
 * @package org.openpsa.invoices
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * PDF Manager
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_invoice_pdf
{
    /**
     * @var org_openpsa_invoices_invoice_dba
     */
    private $invoice;

    public function __construct(org_openpsa_invoices_invoice_dba $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * @return midcom_db_attachment|NULL
     */
    public function get_attachment($autocreate = false)
    {
        $pdf_files = org_openpsa_helpers::get_dm2_attachments($this->invoice, "pdf_file");
        if (!empty($pdf_files))
        {
            return reset($pdf_files);
        }
        if (!$autocreate)
        {
            return null;
        }
        return $this->render_and_attach();
    }

    public function has_manual_attachment()
    {
        if ($attachment = $this->get_attachment())
        {
            // check if auto generated parameter is same as md5 in current-file
            // if not the file was manually uploaded
            if ($checksum = $attachment->get_parameter('org.openpsa.invoices', 'auto_generated'))
            {
                $blob = new midgard_blob($attachment->__object);
                return $checksum !== md5_file($blob->get_path());
            }
        }
        return false;
    }

    public function render_and_attach()
    {
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.invoices', 'config')->get('invoice_pdfbuilder_class');
        if (!class_exists($client_class))
        {
            throw new midcom_error('Could not find PDF renderer ' . $client_class);
        }

        if ($this->invoice->date == 0 || $this->invoice->deliverydate == 0)
        {
            if ($this->invoice->date == 0)
            {
                $this->invoice->date = time();
            }
            if ($this->invoice->deliverydate == 0)
            {
                $this->invoice->deliverydate = time();
            }
        }
        // renders the pdf and attaches it to the invoice
        $pdf_builder = new $client_class($this->invoice);
        $generator = midcom::get()->serviceloader->load('midcom_core_service_urlgenerator');
        $filename = $generator->from_string($this->invoice->get_label()) . '.pdf';

        // tmp filename
        $tmp_file = midcom::get()->config->get('midcom_tempdir') . "/". $filename;

        // render pdf to tmp filename
        $pdf_builder->render($tmp_file);

        // cleanup old attachments
        if ($attachment = $this->get_attachment())
        {
            $attachment->delete();
        }

        $attachment = $this->invoice->create_attachment($filename, $this->invoice->get_label(), "application/pdf");

        if (!$attachment)
        {
            throw new midcom_error("Failed to create invoice attachment for pdf");
        }

        if (!$attachment->copy_from_file($tmp_file))
        {
            throw new midcom_error("Failed to copy pdf from " . $tmp_file . " to attachment");
        }

        // set parameter for datamanager to find the pdf
        if (   !$this->invoice->set_parameter("midcom.helper.datamanager2.type.blobs", "guids_pdf_file", $attachment->guid . ":" . $attachment->guid)
            || !$attachment->set_parameter('org.openpsa.invoices', 'auto_generated', md5_file($tmp_file)))
        {
            throw new midcom_error("Failed to create attachment parameters, last midgard error was: " . midcom_connection::get_error_string());
        }
        // only save potential invoice changes when everything worked (also refreshes revised timestamp)
        $this->invoice->update();
        return $attachment;
    }
}