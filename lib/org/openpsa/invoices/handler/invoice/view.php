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
    use org_openpsa_invoices_handler;

    private org_openpsa_invoices_invoice_dba $invoice;

    /**
     * Generates an object view.
     */
    public function _handler_read(string $guid, array &$data)
    {
        $this->invoice = new org_openpsa_invoices_invoice_dba($guid);
        $dm = $this->load_datamanager();

        $data['object'] = $this->invoice;
        $data['object_view'] = $dm->get_content_html();
        $data['invoice_items'] = $this->invoice->get_invoice_items();

        $this->populate_toolbar();
        $this->update_breadcrumb();

        midcom::get()->metadata->set_request_metadata($this->invoice->metadata->revised, $guid);
        $this->bind_view_to_object($this->invoice, $dm->get_schema()->get_name());
        midcom::get()->head->set_pagetitle($this->_l10n->get('invoice') . ' ' . $this->invoice->get_label());

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.helpers/fileinfo.css");
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/" . $this->_component . "/invoices.js");

        return $this->show('admin-read');
    }

    private function load_datamanager() : datamanager
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        if ($options = $this->get_vat_options($this->_config->get('vat_percentages'))) {
            $schemadb->get('default')->get_field('vat')['type_config']['options'] = $options;
        }

        if ($this->_config->get('invoice_pdfbuilder_class')) {
            $schemadb->get('default')->get_field('pdf_file')['hidden'] = false;
        }

        $dm = new datamanager($schemadb);
        return $dm->set_storage($this->invoice);
    }

    private function populate_toolbar()
    {
        $buttons = [];
        if ($this->invoice->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager');
            $buttons[] = $workflow->get_button($this->router->generate('invoice_edit', ['guid' => $this->invoice->guid]), [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }

        $buttons[] = [
            MIDCOM_TOOLBAR_URL => $this->router->generate('invoice_items', ['guid' => $this->invoice->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit invoice items'),
            MIDCOM_TOOLBAR_GLYPHICON => 'list-ul',
            MIDCOM_TOOLBAR_ENABLED => $this->invoice->can_do('midgard:update'),
        ];

        if (!$this->invoice->sent) {
            $buttons[] = $this->build_button('mark_sent', 'paper-plane-o');
        } elseif (!$this->invoice->paid) {
            $buttons[] = $this->build_button('mark_paid', 'check');
        }

        if ($this->_config->get('invoice_pdfbuilder_class')) {
            $button = $this->build_button('create_pdf', 'file-pdf-o');
            $pdf_helper = new org_openpsa_invoices_invoice_pdf($this->invoice);
            $button[MIDCOM_TOOLBAR_OPTIONS] = $pdf_helper->get_button_options();
            $buttons[] = $button;

            // sending per email enabled in billing data?
            $billing_data = org_openpsa_invoices_billing_data_dba::get_by_object($this->invoice);
            if (    !$this->invoice->sent && intval($billing_data->sendingoption) == 2) {
                $buttons[] = $workflow->get_button(
                    $this->router->generate('invoice_send_by_mail', ['guid' => $this->invoice->guid]),
                    [
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('send_by_mail'),
                        MIDCOM_TOOLBAR_GLYPHICON => 'paper-plane',
                        MIDCOM_TOOLBAR_ENABLED => true,
                    ]
                );
            }

            if ($this->invoice->get_status() == 'overdue' && intval($billing_data->sendingoption) == 2) {
                $buttons[] = $workflow->get_button(
                    $this->router->generate('invoice_send_payment_reminder', ['guid' => $this->invoice->guid]),
                    [
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('send_payment_reminder'),
                        MIDCOM_TOOLBAR_GLYPHICON => 'paper-plane',
                        MIDCOM_TOOLBAR_ENABLED => true,
                    ]
                );
            }
        }

        if ($this->_config->get('invoice_pdfbuilder_reminder_class') && $this->invoice->get_status() == 'overdue') {
            $button = $this->build_button('create_payment_warning', 'file-pdf-o');
            $pdf_helper = new org_openpsa_invoices_invoice_pdf($this->invoice);
            $button[MIDCOM_TOOLBAR_OPTIONS] = $pdf_helper->get_button_options('reminder');
            $buttons[] = $button;
        }

        if ($this->invoice->is_cancelable()) {
            $buttons[] = $this->build_button('create_cancelation', 'ban');
        }

        $this->_view_toolbar->add_items($buttons);
        $this->add_next_previous($this->invoice, 'invoice/');
    }

    private function build_button(string $action, string $icon) : array
    {
        return [
            MIDCOM_TOOLBAR_URL => 'invoice/action/' . $action . '/',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get($action),
            MIDCOM_TOOLBAR_GLYPHICON => $icon,
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
     */
    private function update_breadcrumb()
    {
        if ($customer = $this->invoice->get_customer()) {
            $this->add_breadcrumb($this->router->generate('list_customer_all', ['guid' => $customer->guid]), $customer->get_label());
        }

        $this->add_breadcrumb("", $this->_l10n->get('invoice') . ' ' . $this->invoice->get_label());
    }
}
