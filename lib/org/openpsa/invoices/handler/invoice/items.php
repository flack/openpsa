<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice item handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_invoice_items extends midcom_baseclasses_components_handler
{
    /**
     * The invoice we're working with
     *
     * @param org_openpsa_invoices_invoice_dba
     */
    private $_object = null;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_items($handler_id, array $args, array &$data)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);

        $data['entries'] = [];

        $invoice_sum = 0;
        foreach ($this->_object->get_invoice_items() as $item) {
            $entry = [];
            $entry['id'] = $item->id;
            try {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($item->deliverable);
                $entry['deliverable'] = $deliverable->title;
            } catch (midcom_error $e) {
                $entry['deliverable'] = '';
            }
            try {
                $task = org_openpsa_projects_task_dba::get_cached($item->task);
                $entry['task'] = $task->title;
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
        $data['grid'] = new org_openpsa_widgets_grid('invoice_items', 'local');
        $data['grid']->set_footer_data(['sum' => $invoice_sum]);
        $this->_prepare_output();
    }

    private function _prepare_output()
    {
        $title = $this->_l10n->get('invoice') . ' ' . $this->_object->get_label();
        $this->add_breadcrumb("invoice/" . $this->_object->guid . "/", $title);
        $this->add_breadcrumb("invoice/" . $this->_object->guid . "/", $this->_l10n->get('edit invoice items') . ': ' . $title);

        midcom::get()->head->set_pagetitle($this->_l10n->get('edit invoice items') . ': ' . $title);
        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => "invoice/recalculation/{$this->_object->guid}/",
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recalculate_by_reports'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
        ]);

        $this->_master->add_next_previous($this->_object, $this->_view_toolbar, 'invoice/items/');

        // This is used for the Drag&Drop sorting
        midcom::get()->head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/widgets/sortable.min.js');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_items($handler_id, array &$data)
    {
        midcom_show_style('show-items');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_itemedit($handler_id, array $args, array &$data)
    {
        $this->_verify_post_data();

        $invoice = new org_openpsa_invoices_invoice_dba($args[0]);

        if ($_POST['oper'] == 'edit') {
            if (strpos($_POST['id'], 'new_') === 0) {
                $item = new org_openpsa_invoices_invoice_item_dba();
                $item->invoice = $invoice->id;
                $item->create();
            } else {
                $item = new org_openpsa_invoices_invoice_item_dba((int) $_POST['id']);
            }
            $item->units = (float) str_replace(',', '.', $_POST['quantity']);
            $item->pricePerUnit = (float) str_replace(',', '.', $_POST['price']);
            $item->description = $_POST['description'];

            if (!$item->update()) {
                throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
            }
        } else {
            $item = new org_openpsa_invoices_invoice_item_dba((int) $_POST['id']);
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
            'oldid' => $_POST['id']
        ];
        return new midcom_response_json($result);
    }

    private function _verify_post_data()
    {
        if (   empty($_POST['oper'])
            || !isset($_POST['id'])
            || !isset($_POST['description'])
            || !isset($_POST['price'])
            || !isset($_POST['quantity'])) {
            throw new midcom_error('Incomplete POST data');
        }
        if (!in_array($_POST['oper'], ['edit', 'del'])) {
            throw new midcom_error('Invalid operation "' . $_POST['oper'] . '"');
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_itemposition($handler_id, array $args, array &$data)
    {
        $item = new org_openpsa_invoices_invoice_item_dba((int) $_POST['id']);
        $item->position = $_POST['position'];

        if (!$item->update()) {
            throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
        }
        return new midcom_response_json([]);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_recalculation($handler_id, array $args, array &$data)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);
        $this->_object->_recalculate_invoice_items();

        return new midcom_response_relocate("invoice/items/" . $this->_object->guid . "/");
    }
}
