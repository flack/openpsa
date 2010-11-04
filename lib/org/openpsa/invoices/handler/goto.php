<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: goto.php 26714 2010-10-22 19:25:07Z flack $
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

    function __construct()
    {
        parent::__construct();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
    */
    function _handler_goto($handler_id, $args, &$data)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        if(!isset($_GET['query']))
        {
            $MessageContent = sprintf
            (
                $this->_l10n->get('no invoice number was handed over'),
                $this->_l10n->get($_GET['query'])
            );

            $_MIDCOM->uimessages->add($this->_l10n->get('invoice was not found'), $MessageContent, 'info');
            $_MIDCOM->relocate($prefix);
        }

        $invoicenumber = $_GET['query'] ;
        if( (int) $invoicenumber == 0)
        {
            $MessageContent = sprintf
            (
                $this->_l10n->get('there is no invoice with number %s'),
                $this->_l10n->get($_GET['query'])
            );

            $_MIDCOM->uimessages->add($this->_l10n->get('invoice was not found'), $MessageContent, 'info');
            $_MIDCOM->relocate($prefix);
        }

        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_constraint('number', '=', (int) $invoicenumber);

        switch (count($searched_invoice = $qb->execute()))
        {
            case 1:
                $_MIDCOM->relocate($prefix . 'invoice/' .$searched_invoice[0]->guid .'/');
            case 0:
                $MessageContent = sprintf
                (
                    $this->_l10n->get('there is no invoice with number %s'),
                    $this->_l10n->get($_GET['query'])
                );

                $_MIDCOM->uimessages->add($this->_l10n->get('invoice was not found'), $MessageContent, 'info');

            default:
                $_MIDCOM->relocate($prefix);
        }

        return true;
    }

}
?>