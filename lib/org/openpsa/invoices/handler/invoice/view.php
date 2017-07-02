<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * Invoice read handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_invoice_view extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_invoices_invoice_dba
     */
    private $invoice;

    /**
     * Generates an object view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_read($handler_id, array $args, array &$data)
    {
        $this->invoice = new org_openpsa_invoices_invoice_dba($args[0]);
        $dm = $this->load_datamanager();

        $qb = org_openpsa_projects_hour_report_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', $this->invoice->id);

        $data['reports'] = $qb->execute();
        $data['object'] = $this->invoice;
        $data['object_view'] = $dm->get_content_html();
        $data['invoice_items'] = $this->invoice->get_invoice_items();

        $this->populate_toolbar($handler_id);
        $this->update_breadcrumb($handler_id);

        midcom::get()->metadata->set_request_metadata($this->invoice->metadata->revised, $this->invoice->guid);
        $this->bind_view_to_object($this->invoice, $dm->get_schema()->get_name());
        midcom::get()->head->set_pagetitle($this->_l10n->get('invoice') . ' ' . $this->invoice->get_label());

        org_openpsa_widgets_grid::add_head_elements();
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/" . $this->_component . "/invoices.js");
    }

    /**
     * Shows the loaded object.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_read($handler_id, array &$data)
    {
        midcom_show_style('admin-read');
    }

    private function load_datamanager()
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        $vat_array = explode(',', $this->_config->get('vat_percentages'));
        if (!empty($vat_array)) {
            $vat_values = [];
            foreach ($vat_array as $vat) {
                $vat_values[$vat] = "{$vat}%";
            }
        }
        $vat_field =& $schemadb->get('default')->get_field('vat');
        $vat_field['type_config']['options'] = $vat_values;

        if ($this->_config->get('invoice_pdfbuilder_class')) {
            $pdf_field =& $schemadb->get('default')->get_field('pdf_file');
            $pdf_field['hidden'] = false;
        }

        $dm = new datamanager($schemadb);
        return $dm->set_storage($this->invoice);
    }

    private function populate_toolbar($handler_id)
    {
        $buttons = [];
        if ($this->invoice->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager');
            $buttons[] = $workflow->get_button("invoice/edit/{$this->invoice->guid}/", [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }

        if ($this->invoice->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $this->invoice]);
            $buttons[] = $workflow->get_button("invoice/delete/{$this->invoice->guid}/");
        }

        $buttons[] = [
            MIDCOM_TOOLBAR_URL => "invoice/items/{$this->invoice->guid}/",
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit invoice items'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            MIDCOM_TOOLBAR_ENABLED => $this->invoice->can_do('midgard:update'),
        ];

        if (!$this->invoice->sent) {
            $buttons[] = $this->build_button('mark_sent', 'stock-icons/16x16/stock_mail-reply.png');
        } elseif (!$this->invoice->paid) {
            $buttons[] = $this->build_button('mark_paid', 'stock-icons/16x16/ok.png');
        }

        if ($this->_config->get('invoice_pdfbuilder_class')) {
            $button = $this->build_button('create_pdf', 'stock-icons/16x16/printer.png');
            $pdf_helper = new org_openpsa_invoices_invoice_pdf($this->invoice);
            $button[MIDCOM_TOOLBAR_OPTIONS] = $pdf_helper->get_button_options();
            $buttons[] = $button;

            // sending per email enabled in billing data?
            $billing_data = org_openpsa_invoices_billing_data_dba::get_by_object($this->invoice);
            if (    !$this->invoice->sent
                 && intval($billing_data->sendingoption) == 2) {
                $buttons[] = $this->build_button('send_by_mail', 'stock-icons/16x16/stock_mail-reply.png');
            }
        }

        if ($this->invoice->is_cancelable()) {
            $buttons[] = $this->build_button('create_cancelation', 'stock-icons/16x16/cancel.png');
        }

        $this->_view_toolbar->add_items($buttons);
        org_openpsa_relatedto_plugin::add_button($this->_view_toolbar, $this->invoice->guid);

        $this->_master->add_next_previous($this->invoice, $this->_view_toolbar, 'invoice/');
    }

    private function build_button($action, $icon)
    {
        return [
            MIDCOM_TOOLBAR_URL => 'invoice/action/' . $action . '/',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get($action),
            MIDCOM_TOOLBAR_ICON => $icon,
            MIDCOM_TOOLBAR_POST => true,
            MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                'id' => $this->invoice->id,
                'relocate' => true
            ],
            MIDCOM_TOOLBAR_ENABLED => $this->invoice->can_do('midgard:update'),
        ];
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     *
     * @param string $handler_id The current handler
     */
    private function update_breadcrumb($handler_id)
    {
        if ($customer = $this->invoice->get_customer()) {
            $this->add_breadcrumb("list/customer/all/{$customer->guid}/", $customer->get_label());
        }

        $this->add_breadcrumb("invoice/" . $this->invoice->guid . "/", $this->_l10n->get('invoice') . ' ' . $this->invoice->get_label());
    }
}
