<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice interface class.
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler, $args)
    {
        org_openpsa_widgets_contact::add_head_elements();
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.invoices/invoices.css");
    }

    public static function add_head_elements_for_invoice_grid()
    {
        $invoices_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.invoices');

        org_openpsa_widgets_grid::add_head_elements();
        midcom::get('head')->add_jscript('var INVOICES_URL ="' . $invoices_url . 'invoice/process/";');
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.invoices/invoices.js');
    }

    public function render_invoice_actions(org_openpsa_invoices_invoice_dba $invoice)
    {
        $action = '';
        $next_marker = array();

        // unsent invoices
        if ($invoice->sent == 0)
        {
            // sending per mail enabled in billing data?
            $billing_data = $invoice->get_billing_data();
            // only show if mail was chosen as option
            if (intval($billing_data->sendingoption) == 2)
            {
                $next_marker[] = 'sent_per_mail';
            }
            else
            {
                $next_marker[] = 'sent';
            }
        }
        // not paid yet
        else if (!$invoice->paid)
        {
            $next_marker[] = 'paid';
        }
        else
        {
            $action .= strftime('%x', $invoice->paid);
        }

        // generate next action buttons
        if (   $invoice->can_do('midgard:update')
            && count($next_marker) > 0)
        {
            foreach ($next_marker as $mark)
            {
                $action .= '<button id="invoice_' . $invoice->guid . '" class="yes mark_' . $mark . '">';
                $action .= $this->_l10n->get('mark ' . $mark);
                $action .= '</button>';
            }
        }
        return $action;
    }

    public function add_next_previous($object, $toolbar, $urlprefix)
    {
        if ($object->number > 1)
        {
            $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
            $qb->add_constraint('number', '<', $object->number);
            $qb->set_limit(1);
            $qb->add_order('number', 'DESC');
            $results = $qb->execute();

            if (sizeof($results) == 1)
            {
                $toolbar->add_item
                (
                    array
                    (
                        MIDCOM_TOOLBAR_URL => $urlprefix . $results[0]->guid . '/',
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/back.png',
                        MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                    )
                );
            }
        }
        if (($object->number + 1) < $object->generate_invoice_number())
        {
            $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
            $qb->add_constraint('number', '>', $object->number);
            $qb->set_limit(1);
            $qb->add_order('number', 'ASC');
            $results = $qb->execute();

            $toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $urlprefix . $results[0]->guid . '/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/next.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                )
            );
        }
    }
}
?>