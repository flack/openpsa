<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice goto Handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_goto extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_goto($handler_id, array $args, array &$data)
    {
        if (!isset($_GET['query'])) {
            midcom::get()->uimessages->add($this->_l10n->get('invoice was not found'), $this->_l10n->get('no invoice number was handed over'), 'info');
            return new midcom_response_relocate('');
        }

        $invoicenumber = (int) $_GET['query'] ;

        if ($invoice = org_openpsa_invoices_invoice_dba::get_by_number($invoicenumber)) {
            return new midcom_response_relocate('invoice/' . $invoice->guid . '/');
        }

        $MessageContent = sprintf(
            $this->_l10n->get('there is no invoice with number %s'),
            $this->_l10n->get($invoicenumber)
        );

        midcom::get()->uimessages->add($this->_l10n->get('invoice was not found'), $MessageContent, 'info');

        return new midcom_response_relocate('');
    }
}
