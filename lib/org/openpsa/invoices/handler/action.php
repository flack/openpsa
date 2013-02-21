<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Invoice action handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_action extends midcom_baseclasses_components_handler
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
    public function _handler_process($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        if (   empty($_POST['id'])
            || empty($_POST['action']))
        {
            throw new midcom_error('Incomplete POST data');
        }

        $invoice = new org_openpsa_invoices_invoice_dba((int) $_POST['id']);
        $invoice->require_do('midgard:update');

        midcom::get()->skip_page_style = true;

        $data['message'] = array
        (
            'title' => $this->_l10n->get($this->_component),
            'type' => 'error'
        );
        switch ($_POST['action'])
        {
            case 'mark_sent':
                $data['success'] = $this->_mark_as_sent($invoice);
                break;
            case 'send_by_mail':
                $data['success'] = $this->_send_by_mail($invoice);
                break;
            case 'mark_paid':
                $data['success'] = $this->_mark_as_paid($invoice);
                break;
            case 'create_cancelation':
                $data['success'] = $this->_create_cancelation($invoice);
                break;
            default:
                debug_add("The action " . $_POST["action"] . " is unknown");
                throw new midcom_error_notfound('Unknown operation');
        }

        if ($data['success'])
        {
            $data['message']['type'] = 'ok';
        }

        if (!empty($_POST['relocate']))
        {
            midcom::get('uimessages')->add($this->_l10n->get('org.openpsa.invoices'), $data['message']['message'], $data['message']['type']);
            return new midcom_response_relocate('');
        }

        $data['next_action'] = $this->_master->render_invoice_actions($invoice);
        $data['invoice'] = $invoice;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_process($handler_id, array &$data)
    {
        midcom_show_style('admin-process');
    }

    private function _create_cancelation(org_openpsa_invoices_invoice_dba $invoice)
    {
        // can be canceled?
        if (!$invoice->is_cancelable())
        {
            $this->_request_data['message']['message'] = sprintf($this->_l10n->get('cancelation for invoice %s already exists'), $invoice->get_label());
            return false;
        }

        // process
        $reverse_sum = $invoice->sum * (-1);

        $cancelation_invoice = new org_openpsa_invoices_invoice_dba();
        $cancelation_invoice->customerContact = $invoice->customerContact;
        $cancelation_invoice->sum = $reverse_sum;
        $cancelation_invoice->number = $cancelation_invoice->generate_invoice_number();
        $cancelation_invoice->vat = $invoice->vat;
        $stat = $cancelation_invoice->create();

        $error_msg = sprintf($this->_l10n->get('could not create cancelation for invoice %s'), $invoice->get_label());
        if (!$stat)
        {
            $this->_request_data['message']['message'] = $error_msg;
            return false;
        }

        // add invoice item to cancelation invoice
        $invoice_item = new org_openpsa_invoices_invoice_item_dba();
        $invoice_item->invoice = $cancelation_invoice->id;
        $invoice_item->description = sprintf($this->_l10n->get('cancelation for invoice %s'), $invoice->number);
        $invoice_item->units = 1;
        $invoice_item->pricePerUnit = $reverse_sum;
        $stat = $invoice_item->create();

        if (!$stat)
        {
            // cleanup
            $cancelation_invoice->delete();
            $this->_request_data['message']['message'] = $error_msg;
            return false;
        }

        // if storno invoice was created, mark the related invoice as sent and paid
        $time = time();

        if (!$invoice->sent)
        {
            $invoice->sent = $time;
        }
        if (!$invoice->paid)
        {
            $invoice->paid = $time;
        }
        $invoice->cancelationInvoice = $cancelation_invoice->id;
        $invoice->update();
        if (!$stat)
        {
            // cleanup
            $cancelation_invoice->delete();
            $this->_request_data['message']['message'] = $error_msg;
            return false;
        }

        // redirect to invoice page
        midcom::get()->relocate("invoice/" . $cancelation_invoice->guid . "/");
    }

    private function _send_by_mail(org_openpsa_invoices_invoice_dba $invoice)
    {
        $customerCard = org_openpsa_widgets_contact::get($invoice->customerContact);
        $contactDetails = $customerCard->contact_details;
        $invoice_label = $invoice->get_label();
        $invoice_date = date($this->_l10n_midcom->get('short date'), $invoice->date);

        // generate pdf, only if not existing yet
        $pdf_files = org_openpsa_helpers::get_dm2_attachments($invoice, "pdf_file");
        if (count($pdf_files) == 0)
        {
            org_openpsa_invoices_handler_pdf::render_and_attach_pdf($invoice);
            //refresh to get new file. TODO: This should be optimized by changing the render interface
            $pdf_files = org_openpsa_helpers::get_dm2_attachments($invoice, "pdf_file");
        }

        $mail = new org_openpsa_mail();

        // define replacements for subject / body
        $mail->parameters = array
        (
            "INVOICE_LABEL" => $invoice_label,
            "INVOICE_DATE" => $invoice_date,
            "FIRSTNAME" => $contactDetails["firstname"],
            "LASTNAME" => $contactDetails["lastname"]
        );

        $mail->to = $contactDetails["email"];
        $mail->from = $this->_config->get('invoice_mail_from_address');
        $mail->subject = $this->_config->get('invoice_mail_title');
        $mail->body = $this->_config->get('invoice_mail_body');

        // attach pdf to mail
        if ($mail->can_attach())
        {
            foreach ($pdf_files as $attachment)
            {
                $att = array();
                $att['name'] = $attachment->name . ".pdf";
                $att['mimetype'] = "application/pdf";

                $fp = $attachment->open("r");
                if (!$fp)
                {
                    //Failed to open attachment for reading, skip the file
                    continue;
                }

                $att['content'] = '';

                while (!feof($fp))
                {
                    $att['content'] .=  fread($fp, 4096);
                }
                $attachment->close();
                debug_add("adding attachment '{$att['name']}' to attachments array of invoice mail");

                $mail->attachments[] = $att;
                unset($att);
            }
        }

        if (!$mail->send())
        {
            $this->_request_data['message']['message'] = sprintf($this->_l10n->get('unable to deliver mail: %s'), $mail->get_error_message());
            return false;
        }
        else
        {
            $invoice->set_parameter($this->_component, 'sent_by_mail', time());
            return $this->_mark_as_sent($invoice);
        }
    }

    /**
     * helper function - contains code to mark invoice as paid,
     * maybe move it to invoice-class ?
     *
     * @param org_openpsa_invoices_invoice_dba $invoice contains invoice
     */
    private function _mark_as_paid(org_openpsa_invoices_invoice_dba $invoice)
    {
        if (!$invoice->paid)
        {
            $invoice->paid = time();
            if ($invoice->update())
            {
                $this->_request_data['message']['message'] = sprintf($this->_l10n->get('marked invoice %s paid'), $invoice->get_label());
            }
            else
            {
                $this->_request_data['message']['message'] = sprintf($this->_l10n->get('could not mark invoice %s paid'), $invoice->get_label());
                return false;
            }
        }
        return true;
    }

    /**
     * helper function - contains code to mark invoice as sent,
     * maybe move it to invoice-class ?
     *
     * @param org_openpsa_invoices_invoice_dba $invoice contains invoice
     */
    private function _mark_as_sent(org_openpsa_invoices_invoice_dba $invoice)
    {
        if (!$invoice->sent)
        {
            $invoice->sent = time();

            if ($invoice->update())
            {
                $this->_request_data['message']['message'] = sprintf($this->_l10n->get('marked invoice %s sent'), $invoice->get_label());
            }
            else
            {
                $this->_request_data['message']['message'] = sprintf($this->_l10n->get('could not mark invoice %s paid'), $invoice->get_label());
                return false;
            }
            $mc = new org_openpsa_relatedto_collector($invoice->guid, 'org_openpsa_projects_task_dba');
            $tasks = $mc->get_related_objects();

            // Close "Send invoice" task
            foreach ($tasks as $task)
            {
                if (org_openpsa_projects_workflow::complete($task) && !isset($args["no_redirect"]))
                {
                    midcom::get('uimessages')->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked task "%s" finished'), $task->title), 'ok');
                }
            }
        }
        return true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_items($handler_id, array $args, array &$data)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);

        //get invoice_items for this invoice
        $this->_request_data['invoice_items'] = $this->_object->get_invoice_items();
        $this->_request_data['invoice'] = $this->_object;
        $this->_prepare_grid_data();
        $this->_prepare_output();

    }

    private function _prepare_grid_data()
    {
        $this->_request_data['grid'] = new org_openpsa_widgets_grid('invoice_items', 'local');

        $entries = array();

        $invoice_sum = 0;
        foreach ($this->_request_data['invoice_items'] as $item)
        {
            $entry =  array();
            $entry['id'] = $item->id;
            try
            {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($item->deliverable);
                $entry['deliverable'] = $deliverable->title;
            }
            catch (midcom_error $e)
            {
                $entry['deliverable'] = '';
            }
            try
            {
                $task = org_openpsa_projects_task_dba::get_cached($item->task);
                $entry['task'] = $task->title;
            }
            catch (midcom_error $e)
            {
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

            $entries[] = $entry;
        }

        $this->_request_data['entries'] = $entries;
        $this->_request_data['grid']->set_footer_data(array('sum' => $invoice_sum));
    }

    /**
     * Helper function to create invoice items from POST data
     */
    private function _create_invoice_items()
    {
        if (!array_key_exists('invoice_items_new', $_POST))
        {
            return;
        }
        foreach ($_POST['invoice_items_new'] as $item)
        {
            //check if needed properties are passed
            if(    !empty($item['description'])
                && !empty($item['price_per_unit'])
                && !empty($item['units']))
            {
                $new_item = new org_openpsa_invoices_invoice_item_dba();
                $new_item->invoice = $this->_object->id;
                $new_item->description = $item['description'];
                $new_item->pricePerUnit = (float) str_replace(',', '.', $item['price_per_unit']);
                $new_item->units = (float) str_replace(',', '.', $item['units']);
                $new_item->create();
            }
        }
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

        midcom::get()->skip_page_style = true;

        switch ($_POST['oper'])
        {
            case 'edit':
                if (strpos($_POST['id'], 'new_') === 0)
                {
                    $item = new org_openpsa_invoices_invoice_item_dba();
                    $item->invoice = $invoice->id;
                    $item->create();
                }
                else
                {
                    $item = new org_openpsa_invoices_invoice_item_dba((int) $_POST['id']);
                }
                $item->units = (float) str_replace(',', '.', $_POST['quantity']);
                $item->pricePerUnit = (float) str_replace(',', '.', $_POST['price']);
                $item->description = $_POST['description'];

                if (!$item->update())
                {
                    throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
                }
                break;
            case 'del':
                $item = new org_openpsa_invoices_invoice_item_dba((int) $_POST['id']);
                if (!$item->delete())
                {
                    throw new midcom_error('Failed to delete item: ' . midcom_connection::get_error_string());
                }
                break;
            default:
                throw new midcom_error('Invalid operation "' . $_POST['oper'] . '"');
        }
        $data['saved_values'] = array
        (
            'id' => $item->id,
            'quantity' => $item->units,
            'price' => $item->pricePerUnit,
            'description' => $item->description,
            'position' => $item->position,
            'oldid' => $_POST['id']
        );
    }

    private function _verify_post_data()
    {
        if (   empty($_POST['oper'])
            || !isset($_POST['id'])
            || !isset($_POST['description'])
            || !isset($_POST['price'])
            || !isset($_POST['quantity']))
        {
             throw new midcom_error('Incomplete POST data');
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_itemedit($handler_id, array &$data)
    {
        midcom_show_style('show-itemedit');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_itemposition($handler_id, array $args, array &$data)
    {
        midcom::get()->skip_page_style = true;

        $item = new org_openpsa_invoices_invoice_item_dba((int) $_POST['id']);
        $item->position = $_POST['position'];

        if (!$item->update())
        {
            throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
        }
        return new midcom_response_json(array());
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

    private function _prepare_output()
    {
        $this->add_breadcrumb("invoice/" . $this->_object->guid . "/", $this->_l10n->get('invoice') . ' ' . $this->_object->get_label());
        $this->add_breadcrumb
        (
            "invoice/" . $this->_object->guid . "/",
            $this->_l10n->get('edit invoice items') . ': ' . $this->_l10n->get('invoice') . ' ' . $this->_object->get_label()
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "invoice/recalculation/{$this->_object->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recalculate_by_reports'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_object->can_do('midgard:update'),
            )
        );

        $this->_master->add_next_previous($this->_object, $this->_view_toolbar, 'invoice/items/');

        //This Source is used (and necessary) for the Drag&Drop sorting of grid's <tr>s
        midcom::get('head')->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.sortable.min.js');
    }
}
?>