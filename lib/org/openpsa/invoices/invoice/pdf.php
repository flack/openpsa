<?php
/**
 * @package org.openpsa.invoices
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\storage\blobs;

/**
 * PDF Manager
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_invoice_pdf
{
    private org_openpsa_invoices_invoice_dba $invoice;

    public function __construct(org_openpsa_invoices_invoice_dba $invoice)
    {
        $this->invoice = $invoice;
    }

    public function get_attachment(bool $autocreate = false, string $kind = null) : ?midcom_db_attachment
    {
        if ($kind == 'reminder') {
            $pdf_files = blobs::get_attachments($this->invoice, "pdf_file_reminder");
        } else {
            $pdf_files = blobs::get_attachments($this->invoice, "pdf_file");
        }
        if (!empty($pdf_files)) {
            return reset($pdf_files);
        }
        if (!$autocreate) {
            return null;
        }
        return $this->render_and_attach();
    }

    public function get_button_options(string $kind = 'invoice') : array
    {
        if ($attachment = $this->get_attachment()) {
            if ($this->invoice->sent && $kind != 'reminder') {
                $message = 'invoice has already been sent. should it be replaced?';
            }
            // check if auto generated parameter is same as md5 in current-file
            // if not the file was manually uploaded
            elseif ($checksum = $attachment->get_parameter('org.openpsa.invoices', 'auto_generated')) {
                if ($checksum !== md5_file($attachment->get_path())) {
                    $message = 'current pdf file was manually uploaded shall it be replaced ?';
                }
            }
        }
        if (empty($message)) {
            return [];
        }
        midcom\workflow\dialog::add_head_elements();
        $l10n_midcom = midcom::get()->i18n->get_l10n();
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.invoices');

        return [
            'data-dialog' => 'confirm',
            'data-dialog-heading' => $l10n->get('create_pdf'),
            'data-dialog-text' => $l10n->get($message),
            'data-dialog-confirm-label' => $l10n_midcom->get('confirm'),
            'data-dialog-cancel-label' => $l10n_midcom->get('cancel')
        ];
    }

    public function render_and_attach(string $kind = null) : midcom_db_attachment
    {
        if ($kind == null) {
            $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.invoices', 'config')->get('invoice_pdfbuilder_class');
        } else {
            $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.invoices', 'config')->get('invoice_pdfbuilder_' . $kind . '_class');
        }
        
        if (!class_exists($client_class)) {
            throw new midcom_error('Could not find PDF renderer ' . $client_class);
        }

        if ($this->invoice->date == 0) {
            $this->invoice->date = time();
        }
        if ($this->invoice->deliverydate == 0) {
            $this->invoice->deliverydate = time();
        }
        // renders the pdf and attaches it to the invoice
        $pdf_builder = new $client_class($this->invoice);
        $l10n = midcom::get()->i18n->get_l10n('org.openpsa.invoices');
        if ($kind == 'reminder') {
            $name = "guids_pdf_file_reminder";
            $filename = midcom_helper_misc::urlize($this->invoice->get_label()) . '_' . lcfirst($l10n->get('reminder')) . '.pdf';
        } else {
            $name = "guids_pdf_file";
            $filename = midcom_helper_misc::urlize($this->invoice->get_label()) . '.pdf';
        }

        // tmp filename
        $tmp_file = midcom::get()->config->get('midcom_tempdir') . "/" . $filename;

        // render pdf to tmp filename
        $pdf_builder->render($tmp_file);
        $attachment = $this->get_attachment(false, $kind);
        if ($attachment) {
            $attachment->name = $filename;
            $attachment->title = $this->invoice->get_label();
            $attachment->mimetype = "application/pdf";
            $attachment->update();
        } else {
            $attachment = $this->invoice->create_attachment($filename, $this->invoice->get_label(), "application/pdf");
            if (   !$attachment
                || !$this->invoice->set_parameter("midcom.helper.datamanager2.type.blobs", $name, $attachment->guid . ":" . $attachment->guid)) {
                throw new midcom_error("Failed to create invoice attachment for pdf: " . midcom_connection::get_error_string());
            }
        }

        if (!$attachment->copy_from_file($tmp_file)) {
            throw new midcom_error("Failed to copy pdf from " . $tmp_file . " to attachment");
        }

        if (!$attachment->set_parameter('org.openpsa.invoices', 'auto_generated', md5_file($tmp_file))) {
            throw new midcom_error("Failed to create attachment parameters, last midgard error was: " . midcom_connection::get_error_string());
        }
        // only save potential invoice changes when everything worked (also refreshes revised timestamp)
        $this->invoice->update();
        return $attachment;
    }
}
