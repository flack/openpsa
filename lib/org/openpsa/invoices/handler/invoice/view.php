<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use midcom\grid\grid;

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
        $data['entries'] = $this->load_invoice_items();
        $data['items_editable'] = $this->invoice->can_do('midgard:update');
        $data['grid'] = new grid('invoice_items', 'local');

        $this->populate_toolbar();
        $this->update_breadcrumb();

        midcom::get()->metadata->set_request_metadata($this->invoice->metadata->revised, $guid);
        $this->bind_view_to_object($this->invoice, $dm->get_schema()->get_name());
        midcom::get()->head->set_pagetitle($this->_l10n->get('invoice') . ' ' . $this->invoice->get_label());

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.helpers/fileinfo.css");
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/" . $this->_component . "/invoices.js");
        if ($data['items_editable']) {
            // This is used for the Drag&Drop sorting
            midcom::get()->head->enable_jquery_ui(['sortable']);
        }

        return $this->show('admin-read');
    }

    private function load_invoice_items() : array
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $sales_url = $siteconfig->get_node_full_url('org.openpsa.sales');
        $projects_url = $siteconfig->get_node_full_url('org.openpsa.projects');

        $entries = [];
        foreach ($this->invoice->get_invoice_items() as $item) {
            $entry = [
                'id' => $item->id,
                'position' => $item->position,
                'description' => $item->description,
                'price' => $item->pricePerUnit,
                'quantity' => $item->units,
                'sum' => $item->units * $item->pricePerUnit
            ];
            try {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($item->deliverable);
                $entry['deliverable'] = '<i class="fa fa-money" title="' . htmlspecialchars($deliverable->title) . '"></i>';
                if ($sales_url) {
                    $entry['deliverable'] = '<a href="' . $sales_url . 'deliverable/' . $deliverable->guid . '/">' . $entry['deliverable'] . '</a>';
                }
            } catch (midcom_error) {
                $entry['deliverable'] = '';
            }
            try {
                $task = org_openpsa_projects_task_dba::get_cached($item->task);
                $entry['task'] = '<i class="fa fa-calendar-check-o" title="' . htmlspecialchars($task->title) . '"></i>';
                if ($projects_url) {
                    $entry['task'] = '<a href="' . $projects_url . 'task/' . $task->guid . '/">' . $entry['task'] . '</a>';
                }
            } catch (midcom_error) {
                $entry['task'] = '';
            }
            $entries[] = $entry;
        }
        return $entries;
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
            MIDCOM_TOOLBAR_URL => $this->router->generate('recalc_invoice', ['guid' => $this->invoice->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recalculate_by_reports'),
            MIDCOM_TOOLBAR_GLYPHICON => 'refresh',
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
            $billing_data = org_openpsa_invoices_billing_data_dba::get_by_object($this->invoice, true);
            if ($billing_data->sendingoption == 2) {
                if (!$this->invoice->sent) {
                    $buttons[] = $workflow->get_button(
                        $this->router->generate('invoice_send_by_mail', ['guid' => $this->invoice->guid]),
                        [
                            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('invoice_send_by_mail'),
                            MIDCOM_TOOLBAR_GLYPHICON => 'paper-plane',
                            MIDCOM_TOOLBAR_ENABLED => true,
                        ]
                    );
                } elseif ($this->invoice->get_status() == 'overdue') {
                    $buttons[] = $workflow->get_button(
                        $this->router->generate('invoice_send_payment_reminder', ['guid' => $this->invoice->guid]),
                        [
                            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('reminder_send_by_mail'),
                            MIDCOM_TOOLBAR_GLYPHICON => 'paper-plane',
                            MIDCOM_TOOLBAR_ENABLED => true,
                        ]
                    );
                }
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
