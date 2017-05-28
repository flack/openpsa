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
    public function _on_handle($handler, array $args)
    {
        org_openpsa_widgets_contact::add_head_elements();
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.invoices/invoices.css");
    }

    public static function add_head_elements_for_invoice_grid()
    {
        org_openpsa_widgets_grid::add_head_elements();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.invoices/invoices.js');
    }

    public function render_invoice_actions(org_openpsa_invoices_invoice_dba $invoice)
    {
        $actions = '';
        $next = null;

        // unsent invoices
        if ($invoice->sent == 0) {
            // sending per mail enabled in billing data?
            $billing_data = org_openpsa_invoices_billing_data_dba::get_by_object($invoice);
            // only show if mail was chosen as option
            if (intval($billing_data->sendingoption) == 2) {
                $next = 'send_by_mail';
            } else {
                $next = 'mark_sent';
            }
        }
        // not paid yet
        elseif (!$invoice->paid) {
            $next = 'mark_paid';
        } else {
            $actions .= strftime('%Y-%m-%d', $invoice->paid);
        }

        // generate next action buttons
        if (   $next !== null
            && $invoice->can_do('midgard:update')) {
            $actions .= '<button id="invoice_' . $invoice->guid . '" class="yes ' . $next. '">';
            $actions .= $this->_l10n->get($next);
            $actions .= '</button>';
        }
        return $actions;
    }

    public function prepare_toolbar($mode)
    {
        if ($mode !== 'dashboard') {
            $this->_view_toolbar->add_item(
                array(
                    MIDCOM_TOOLBAR_URL => '',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('dashboard'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
                )
            );
        }
        if (midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba')) {
            $workflow = $this->get_workflow('datamanager2');
            $this->_view_toolbar->add_item($workflow->get_button('invoice/new/', array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create invoice'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/printer.png',
            )));
        }
    }

    public function add_next_previous($object, $toolbar, $urlprefix)
    {
        if ($object->number > 1) {
            $mc = org_openpsa_invoices_invoice_dba::new_collector();
            $mc->add_constraint('number', '<', $object->number);
            $mc->set_limit(1);
            $mc->add_order('number', 'DESC');
            $results = $mc->list_keys();

            if (sizeof($results) == 1) {
                $toolbar->add_item(
                    array(
                        MIDCOM_TOOLBAR_URL => $urlprefix . key($results) . '/',
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/back.png',
                        MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                    )
                );
            }
        }

        if (($object->number + 1) < $object->generate_invoice_number()) {
            $mc = org_openpsa_invoices_invoice_dba::new_collector();
            $mc->add_constraint('number', '>', $object->number);
            $mc->set_limit(1);
            $mc->add_order('number', 'ASC');
            $results = $mc->list_keys();

            if (sizeof($results) == 1) {
                $toolbar->add_item(
                    array(
                        MIDCOM_TOOLBAR_URL => $urlprefix . key($results) . '/',
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
                        MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/next.png',
                        MIDCOM_TOOLBAR_ACCESSKEY => 'n',
                    )
                );
            }
        }
    }
}
