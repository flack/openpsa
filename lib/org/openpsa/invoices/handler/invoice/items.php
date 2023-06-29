<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\grid\grid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
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

    private org_openpsa_invoices_invoice_dba $_object;

    public function _handler_items(string $guid, array &$data)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba($guid);

        $data['entries'] = [];

        $sales_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.sales');
        $projects_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.projects');

        $invoice_sum = 0;
        foreach ($this->_object->get_invoice_items() as $item) {
            $entry = ['id' => $item->id];
            try {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($item->deliverable);
                $entry['deliverable'] = '<i class="fa fa-money" title="' . $deliverable->title . '"></i>';
                if ($sales_url) {
                    $entry['deliverable'] = '<a href="' . $sales_url . 'deliverable/' . $deliverable->guid . '/">' . $entry['deliverable'] . '</a>';
                }
            } catch (midcom_error $e) {
                $entry['deliverable'] = '';
            }
            try {
                $task = org_openpsa_projects_task_dba::get_cached($item->task);
                $entry['task'] = '<i class="fa fa-calendar-check-o" title="' . $task->title . '"></i>';
                if ($projects_url) {
                    $entry['task'] = '<a href="' . $projects_url . 'task/' . $task->guid . '/">' . $entry['task'] . '</a>';
                }
            } catch (midcom_error $e) {
                $entry['task'] = '';
            }

            $entry['description'] = $item->description;
            $entry['price'] = $item->pricePerUnit;
            $entry['quantity'] = $item->units;
            $entry['position'] = $item->position;

            $item_sum = $item->units * $item->pricePerUnit;
            $invoice_sum += $item_sum;
            $entry['sum'] = $item_sum;
            $entry['action'] = '';

            $data['entries'][] = $entry;
        }

        $data['invoice'] = $this->_object;
        $data['grid'] = new grid('invoice_items', 'local');
        $data['grid']->set_footer_data(['sum' => $invoice_sum]);
        $this->_prepare_output();

        return $this->show('show-items');
    }

    private function _prepare_output()
    {
        $title = $this->_l10n->get('invoice') . ' ' . $this->_object->get_label();
        $this->add_breadcrumb($this->router->generate('invoice', ['guid' => $this->_object->guid]), $title);
        $this->add_breadcrumb("", $this->_l10n->get('edit invoice items') . ': ' . $title);

        midcom::get()->head->set_pagetitle($this->_l10n->get('edit invoice items') . ': ' . $title);
        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('recalc_invoice', ['guid' => $this->_object->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recalculate_by_reports'),
            MIDCOM_TOOLBAR_GLYPHICON => 'refresh',
            MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
        ]);

        $this->add_next_previous($this->_object, 'invoice/items/');

        // This is used for the Drag&Drop sorting
        midcom::get()->head->enable_jquery_ui(['sortable']);
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

        return new midcom_response_relocate($this->router->generate('invoice_items', ['guid' => $guid]));
    }
}
