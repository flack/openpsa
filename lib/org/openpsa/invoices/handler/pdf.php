<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Invoice PDF Handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_pdf extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_invoices_invoice_dba
     */
    private $_invoice;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_pdf($handler_id, array $args, array &$data)
    {
        $this->_invoice = new org_openpsa_invoices_invoice_dba($args[0]);

        $invoice_url = "invoice/" . $this->_invoice->guid . "/";

        if (array_key_exists('cancel', $_POST))
        {
            return new midcom_response_relocate($invoice_url);
        }

        if ($this->_prepare_invoice_update())
        {
            if (self::render_and_attach_pdf($this->_invoice))
            {
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n->get('pdf created'));
            }
            else
            {
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n->get('pdf creation failed') . ': ' . midcom_connection::get_error_string(), 'error');
            }
            return new midcom_response_relocate($invoice_url);
        }
    }

    /**
     * Check for manually uploaded pdf-file & if user wants to replace it
     *
     * @return boolean True if the update should be executed, false otherwise
     */
    private function _prepare_invoice_update()
    {
        if (array_key_exists('save', $_POST))
        {
            return true;
        }

        $this->_request_data['confirmation_message'] = 'current pdf file was manually uploaded shall it be replaced ?';

        if ($this->_invoice->sent)
        {
            $this->_request_data['confirmation_message'] = 'invoice has already been sent. should it be replaced?';
            return false;
        }

        $pdf_files = org_openpsa_helpers::get_dm2_attachments($this->_invoice, "pdf_file");
        if (empty($pdf_files))
        {
            return true;
        }
        foreach ($pdf_files as $attachment)
        {
            // check if auto generated parameter is same as md5 in current-file
            // if not the file was manually uploaded
            if ($checksum = $attachment->get_parameter('org.openpsa.invoices', 'auto_generated'))
            {
                $blob = new midgard_blob($attachment->__object);
                // check if md5 sum equals the one saved in auto_generated
                if ($checksum == md5_file($blob->get_path()))
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_pdf($handler_id, array &$data)
    {
        // if attachment was manually uploaded show confirm if file should be replaced
        midcom_show_style('show-confirm');
    }

    public static function render_and_attach_pdf(org_openpsa_invoices_invoice_dba $invoice)
    {
        if ($invoice->date == 0 || $invoice->deliverydate == 0)
        {
            $time = time();
            if ($invoice->date == 0)
            {
                $invoice->date = $time;
            }
            if ($invoice->deliverydate == 0)
            {
                $invoice->deliverydate = $time;
            }
            $invoice->update();
        }
        // renders the pdf and attaches it to the invoice
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.invoices', 'config')->get('invoice_pdfbuilder_class');
        if (!class_exists($client_class))
        {
            throw new midcom_error('Could not find PDF renderer ' . $client_class);
        }
        $pdf_builder = new $client_class($invoice);
        $generator = midcom::get()->serviceloader->load('midcom_core_service_urlgenerator');
        $filename = $generator->from_string($invoice->get_label()) . '.pdf';

        // tmp filename
        $tmp_file = midcom::get()->config->get('midcom_tempdir') . "/". $filename;

        // render pdf to tmp filename
        $pdf_builder->render($tmp_file);

        // cleanup old attachments
        $pdf_files = org_openpsa_helpers::get_dm2_attachments($invoice, "pdf_file");

        foreach ($pdf_files as $attachment)
        {
            $attachment->delete();
        }

        $attachment = $invoice->create_attachment($filename, $invoice->get_label(), "application/pdf");

        if (!$attachment)
        {
            debug_add("Failed to create invoice attachment for pdf", MIDCOM_LOG_ERROR);
            return false;
        }

        if (!$attachment->copy_from_file($tmp_file))
        {
            debug_add("Failed to copy pdf from " . $tmp_file . " to attachment", MIDCOM_LOG_ERROR);
            return false;
        }

        // set parameter for datamanager to find the pdf
        if (   !$invoice->set_parameter("midcom.helper.datamanager2.type.blobs", "guids_pdf_file", $attachment->guid . ":" . $attachment->guid)
            || !$attachment->set_parameter('org.openpsa.invoices', 'auto_generated', md5_file($tmp_file)))
        {
            debug_add("Failed to create attachment parameters, last midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return false;
        }
        return true;
    }
}
