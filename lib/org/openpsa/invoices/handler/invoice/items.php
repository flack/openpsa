<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\grid\grid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use midcom\grid\editor;

/**
 * Invoice item handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_invoice_items extends midcom_baseclasses_components_handler
{
    use org_openpsa_invoices_handler;

    private org_openpsa_invoices_invoice_dba $invoice;

    public function _handler_items(string $guid, array &$data)
    {
        $this->invoice = new org_openpsa_invoices_invoice_dba($guid);

        $data['invoice'] = $this->invoice;
        $data['entries'] = $this->load_invoice_items();
        $data['editable'] = $this->invoice->can_do('midgard:update');
        $data['grid'] = new grid('invoice_items', 'local');

        $this->prepare_output($data['editable']);

        return $this->show('show-items');
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

    private function prepare_output(bool $editable)
    {
        $label = $this->_l10n->get('invoice') . ' ' . $this->invoice->get_label();
        $title = $this->_l10n->get('invoice items');

        $this->add_breadcrumb($this->router->generate('invoice', ['guid' => $this->invoice->guid]), $label);
        $this->add_breadcrumb("", $title);
        midcom::get()->head->set_pagetitle($title . ': ' . $label);

        if ($editable) {
            $this->_view_toolbar->add_item([
                MIDCOM_TOOLBAR_URL => '#',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('add new invoice item'),
                MIDCOM_TOOLBAR_GLYPHICON => 'plus',
                MIDCOM_TOOLBAR_OPTIONS => [
                    'rel' => 'directlink',
                    'id' => 'add-invoice-item',
                ]
            ]);
        }
        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('recalc_invoice', ['guid' => $this->invoice->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recalculate_by_reports'),
            MIDCOM_TOOLBAR_GLYPHICON => 'refresh',
            MIDCOM_TOOLBAR_ENABLED => $editable,
        ]);
        $this->add_next_previous($this->invoice, 'invoice/items/');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/" . $this->_component . "/invoices.js");
        if ($editable) {
            // This is used for the Drag&Drop sorting
            midcom::get()->head->enable_jquery_ui(['sortable']);
        }
    }

    public function _handler_itemedit(Request $request, string $guid)
    {
        $invoice = new org_openpsa_invoices_invoice_dba($guid);
        $editor = new editor($request->request, ['description', 'price', 'quantity']);

        $id = $editor->get_id();
        if ($editor->is_delete()) {
            $item = new org_openpsa_invoices_invoice_item_dba($id);
            if (!$item->delete()) {
                throw new midcom_error('Failed to delete item: ' . midcom_connection::get_error_string());
            }
        } else {
            if (!$id) {
                $item = new org_openpsa_invoices_invoice_item_dba();
                $item->invoice = $invoice->id;
                $item->create();
            } else {
                $item = new org_openpsa_invoices_invoice_item_dba($id);
            }
            $data = $editor->get_data();
            $item->units = (float) str_replace(',', '.', $data['quantity']);
            $item->pricePerUnit = (float) str_replace(',', '.', $data['price']);
            $item->description = $data['description'];

            if (!$item->update()) {
                throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
            }
        }
        return $editor->get_response([
            'id' => $item->id,
            'quantity' => $item->units,
            'price' => $item->pricePerUnit,
            'description' => $item->description,
            'position' => $item->position,
        ]);
    }

    public function _handler_itemposition(Request $request)
    {
        $item = new org_openpsa_invoices_invoice_item_dba($request->request->getInt('id'));
        $item->position = $request->request->getInt('position');

        if (!$item->update()) {
            throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
        }
        return new JsonResponse([]);
    }

    public function _handler_recalculation(string $guid)
    {
        $object = new org_openpsa_invoices_invoice_dba($guid);
        $object->_recalculate_invoice_items();

        return new midcom_response_relocate($this->router->generate('invoice', ['guid' => $guid]));
    }
}
