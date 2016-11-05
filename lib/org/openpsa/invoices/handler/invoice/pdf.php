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
class org_openpsa_invoices_handler_invoice_pdf extends midcom_baseclasses_components_handler
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
        $pdf_helper = new org_openpsa_invoices_invoice_pdf($this->_invoice);
        if ($this->_prepare_invoice_update($pdf_helper))
        {
            try
            {
                $pdf_helper->render_and_attach();
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n->get('pdf created'));
            }
            catch (midcom_error $e)
            {
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n->get('pdf creation failed') . ': ' . $e->getMessage(), 'error');
            }
            return new midcom_response_relocate($invoice_url);
        }
    }

    /**
     * Check for manually uploaded pdf-file & if user wants to replace it
     *
     * @return boolean True if the update should be executed, false otherwise
     */
    private function _prepare_invoice_update(org_openpsa_invoices_invoice_pdf $pdf_helper)
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
        return !$pdf_helper->has_manual_attachment();
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
}
