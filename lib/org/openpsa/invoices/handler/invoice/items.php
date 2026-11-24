<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

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
