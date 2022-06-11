<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package org.openpsa.invoices
 */
trait org_openpsa_invoices_handler
{
    public function get_vat_options(string $percentages) : array
    {
        $values = [];
        $vat_array = explode(',', $percentages);
        if (!empty($vat_array)) {
            foreach ($vat_array as $entry) {
                $values[$entry] = "{$entry}%";
            }
        }
        return $values;
    }

    public function render_invoice_actions(org_openpsa_invoices_invoice_dba $invoice) : string
    {
        if ($invoice->paid) {
            return date('Y-m-d', $invoice->paid);
        }
        if (!$invoice->can_do('midgard:update')) {
            return '';
        }

        // unsent invoices
        if ($invoice->sent == 0) {
            // sending per mail enabled in billing data?
            $billing_data = org_openpsa_invoices_billing_data_dba::get_by_object($invoice);
            // only show if mail was chosen as option
            if ($billing_data->sendingoption === 2) {
                $action = 'send_by_mail';
                $icon = '<i class="fa fa-paper-plane"></i>';
            } else {
                $action = 'mark_sent';
                $icon = '<i class="fa fa-paper-plane-o"></i>';
            }
        } else {
            // not paid yet (see above)
            $action = 'mark_paid';
            $icon = '<i class="fa fa-check"></i>';
        }

        // generate next action buttons
        return '<button id="invoice_' . $invoice->guid . '" class="' . $action . '">' . $icon . ' ' . $this->_l10n->get($action) . '</button>';
    }

    public function prepare_toolbar(bool $show_backlink = true)
    {
        if ($show_backlink) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => '',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('dashboard'),
                MIDCOM_TOOLBAR_GLYPHICON => 'chevron-left',
            ]);
        }
        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_invoices_invoice_dba::class)) {
            $workflow = $this->get_workflow('datamanager');
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('invoice_new_nocustomer'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create invoice'),
                MIDCOM_TOOLBAR_GLYPHICON => 'plus',
            ]));
        }
    }

    public function add_next_previous(org_openpsa_invoices_invoice_dba $object, string $urlprefix)
    {
        $items = [];
        $url = '';
        if ($object->number > 1) {
            $mc = org_openpsa_invoices_invoice_dba::new_collector();
            $mc->add_constraint('number', '<', $object->number);
            $mc->set_limit(1);
            $mc->add_order('number', 'DESC');

            if ($results = $mc->list_keys()) {
                $url = $urlprefix . key($results) . '/';
            }
        }
        $items[] = [
            MIDCOM_TOOLBAR_URL => $url,
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('previous'),
            MIDCOM_TOOLBAR_GLYPHICON => 'chevron-left',
            MIDCOM_TOOLBAR_ACCESSKEY => 'p',
            MIDCOM_TOOLBAR_ENABLED => !empty($url)
        ];

        $url = '';
        if (($object->number + 1) < $object->generate_invoice_number()) {
            $mc = org_openpsa_invoices_invoice_dba::new_collector();
            $mc->add_constraint('number', '>', $object->number);
            $mc->set_limit(1);
            $mc->add_order('number', 'ASC');

            if ($results = $mc->list_keys()) {
                $url = $urlprefix . key($results) . '/';
            }
        }
        $items[] = [
            MIDCOM_TOOLBAR_URL => $url,
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('next'),
            MIDCOM_TOOLBAR_GLYPHICON => 'chevron-right',
            MIDCOM_TOOLBAR_ACCESSKEY => 'n',
            MIDCOM_TOOLBAR_ENABLED => !empty($url)
        ];
        org_openpsa_widgets_ui::add_navigation_toolbar($items);
    }
}
