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

/**
 * Invoice item handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_invoice_items extends midcom_baseclasses_components_handler
{
    use org_openpsa_invoices_handler;

    /**
     * The invoice we're working with
     *
     * @var org_openpsa_invoices_invoice_dba
     */
    private $_object;

    public function _handler_items(string $guid, array &$data)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba($guid);

        $data['entries'] = [];

        $sales_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.sales');
        $projects_url = org_openpsa_core_siteconfig::get_instance()->get_node_full_url('org.openpsa.projects');

        $invoice_sum = 0;
        foreach ($this->_object->get_invoice_items() as $item) {
            $entry = [];
            $entry['id'] = $item->id;
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
        $this->_verify_post_data($request->request);

        $invoice = new org_openpsa_invoices_invoice_dba($guid);

        if ($request->request->get('oper') == 'edit') {
            if (str_starts_with($request->request->get('id'), 'new_')) {
                $item = new org_openpsa_invoices_invoice_item_dba();
                $item->invoice = $invoice->id;
                $item->create();
            } else {
                $item = new org_openpsa_invoices_invoice_item_dba($request->request->getInt('id'));
            }
            $item->units = (float) str_replace(',', '.', $request->request->get('quantity'));
            $item->pricePerUnit = (float) str_replace(',', '.', $request->request->get('price'));
            $item->description = $request->request->get('description');

            if (!$item->update()) {
                throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
            }
        } else {
            $item = new org_openpsa_invoices_invoice_item_dba($request->request->getInt('id'));
            if (!$item->delete()) {
                throw new midcom_error('Failed to delete item: ' . midcom_connection::get_error_string());
            }
        }
        $result = [
            'id' => $item->id,
            'quantity' => $item->units,
            'price' => $item->pricePerUnit,
            'description' => $item->description,
            'position' => $item->position,
            'oldid' => $request->request->get('id')
        ];
        return new midcom_response_json($result);
    }

    private function _verify_post_data(ParameterBag $post)
    {
        if (!in_array($post->get('oper'), ['edit', 'del'])) {
            throw new midcom_error('Invalid operation "' . $post->get('oper') . '"');
        }
        if (!$post->has('id') || !$post->has('description') || !$post->has('price') || !$post->has('quantity')) {
            throw new midcom_error('Incomplete POST data');
        }
    }

    public function _handler_itemposition(Request $request)
    {
        $item = new org_openpsa_invoices_invoice_item_dba($request->request->getInt('id'));
        $item->position = $request->request->getInt('position');

        if (!$item->update()) {
            throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
        }
        return new midcom_response_json([]);
    }

    public function _handler_recalculation(string $guid)
    {
        $object = new org_openpsa_invoices_invoice_dba($guid);
        $object->_recalculate_invoice_items();

        return new midcom_response_relocate($this->router->generate('invoice_items', ['guid' => $guid]));
    }
}
