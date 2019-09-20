<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Invoice goto Handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_goto extends midcom_baseclasses_components_handler
{
    /**
     * @param Request $request The request object
     * @return midcom_response_relocate
     */
    public function _handler_goto(Request $request)
    {
        if (!$request->query->has('query')) {
            return $this->fail($this->_l10n->get('no invoice number was handed over'));
        }

        $invoicenumber = $request->query->getInt('query');

        if ($invoice = org_openpsa_invoices_invoice_dba::get_by_number($invoicenumber)) {
            return new midcom_response_relocate($this->router->generate('invoice', ['guid' => $invoice->guid]));
        }

        return $this->fail(sprintf($this->_l10n->get('there is no invoice with number %s'), $invoicenumber));
    }

    private function fail($message) : midcom_response_relocate
    {
        midcom::get()->uimessages->add($this->_l10n->get('invoice was not found'), $message, 'info');
        return new midcom_response_relocate('');
    }
}
