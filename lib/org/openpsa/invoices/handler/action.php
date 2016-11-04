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
    private $message = array();

    private $invoice;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        if (empty($_POST['id']))
        {
            throw new midcom_error('Incomplete POST data');
        }
        $this->invoice = new org_openpsa_invoices_invoice_dba((int) $_POST['id']);
        $this->invoice->require_do('midgard:update');
    }

    private function reply($success, $message)
    {
        $message = array
        (
            'title' => $this->_l10n->get($this->_component),
            'type' => $success ? 'info' : 'error',
            'message' => $message
        );

        if (!empty($_POST['relocate']))
        {
            midcom::get()->uimessages->add($message['title'], $message['message'], $message['type']);
            return new midcom_response_relocate('');
        }

        $result = array
        (
            'success' => $success,
            'action' => $this->_master->render_invoice_actions($this->invoice),
            'due' => strftime('%Y-%m-%d', $this->invoice->due),
            'new_status' => $this->invoice->get_status(),
            'message' => $message
        );
        return new midcom_response_json($result);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create_cancelation($handler_id, array $args, array &$data)
    {
        // can be canceled?
        if (!$this->invoice->is_cancelable())
        {
            return $this->reply(false, sprintf($this->_l10n->get('cancelation for invoice %s already exists'), $this->invoice->get_label()));
        }

        // process
        $cancelation_invoice = new org_openpsa_invoices_invoice_dba();
        $cancelation_invoice->customerContact = $this->invoice->customerContact;
        $cancelation_invoice->customer = $this->invoice->customer;
        $cancelation_invoice->number = $cancelation_invoice->generate_invoice_number();
        $cancelation_invoice->sum = $this->invoice->sum * (-1);
        $cancelation_invoice->vat = $this->invoice->vat;

        if (!$this->invoice->sent)
        {
            $this->invoice->sent = time();
            // if original wasn't sent, we probably don't need to send cancelation
            $cancelation_invoice->sent = time();
        }
        if (!$this->invoice->paid)
        {
            $this->invoice->paid = time();
            // if original wasn't paid, we probably don't need to pay cancelation
            $cancelation_invoice->paid = time();
        }

        if (!$cancelation_invoice->create())
        {
            return $this->reply(false, sprintf($this->_l10n->get('could not create cancelation for invoice %s'), $this->invoice->get_label()));
        }

        // add invoice item(s) to cancelation invoice
        // we need to copy each original item and cancel it
        $items = $this->invoice->get_invoice_items();
        $count = 1;
        foreach ($items as $item)
        {
            $cancelation_item = new org_openpsa_invoices_invoice_item_dba();
            $cancelation_item->invoice = $cancelation_invoice->id;
            $cancelation_item->deliverable = $item->deliverable;
            $cancelation_item->task = $item->task;
            $cancelation_item->description = sprintf($this->_l10n->get('cancelation for invoice %s, item %s'), $this->invoice->number, $count);
            $cancelation_item->units = $item->units;
            $cancelation_item->pricePerUnit = $item->pricePerUnit * (-1);

            if (!$cancelation_item->create())
            {
                // cleanup
                $cancelation_invoice->delete();
                return $this->reply(false, sprintf($this->_l10n->get('could not create item for cancelation invoice %s'), $cancelation_invoice->get_label()));
            }
            $count++;
        }

        $this->invoice->cancelationInvoice = $cancelation_invoice->id;
        if (!$this->invoice->update())
        {
            // cleanup
            $cancelation_invoice->delete();
            return $this->reply(false, sprintf($this->_l10n->get('could not update invoice %s'), $this->invoice->get_label()));
        }

        return new midcom_response_relocate("invoice/" . $cancelation_invoice->guid . "/");
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_send_by_mail($handler_id, array $args, array &$data)
    {
        $customerCard = org_openpsa_widgets_contact::get($this->invoice->customerContact);
        $contactDetails = $customerCard->contact_details;
        $invoice_label = $this->invoice->get_label();

        // check if we got an invoice date..
        if (!$this->invoice->date)
        {
            $this->invoice->date = time();
            $this->invoice->update();
        }
        $invoice_date = $this->_l10n->get_formatter()->date($this->invoice->date);

        // generate pdf, only if not existing yet
        $pdf_files = org_openpsa_helpers::get_dm2_attachments($this->invoice, "pdf_file");
        if (count($pdf_files) == 0)
        {
            org_openpsa_invoices_handler_pdf::render_and_attach_pdf($this->invoice);
            //refresh to get new file. TODO: This should be optimized by changing the render interface
            $pdf_files = org_openpsa_helpers::get_dm2_attachments($this->invoice, "pdf_file");
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

        if ($this->_config->get('invoice_mail_bcc'))
        {
            $mail->bcc = $this->_config->get('invoice_mail_bcc');
        }

        // attach pdf to mail
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

            $att['content'] = stream_get_contents($fp);
            $attachment->close();
            debug_add("adding attachment '{$att['name']}' to attachments array of invoice mail");

            $mail->attachments[] = $att;
        }

        if (!$mail->send())
        {
            return $this->reply(false, sprintf($this->_l10n->get('unable to deliver mail: %s'), $mail->get_error_message()));
        }
        $this->invoice->set_parameter($this->_component, 'sent_by_mail', time());
        return $this->_handler_mark_sent($handler_id, $args, $data);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_mark_paid($handler_id, array $args, array &$data)
    {
        if (!$this->invoice->paid)
        {
            $this->invoice->paid = time();
            if (!$this->invoice->update())
            {
                return $this->reply(false, sprintf($this->_l10n->get('could not mark invoice %s paid'), $this->invoice->get_label()));
            }
        }
        return $this->reply(true, $this->_l10n->get('marked invoice %s paid'), $this->invoice->get_label());
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_mark_sent($handler_id, array $args, array &$data)
    {
        if (!$this->invoice->sent)
        {
            $this->invoice->sent = time();

            if (!$this->invoice->update())
            {
                return $this->reply(false, sprintf($this->_l10n->get('could not mark invoice %s paid'), $this->invoice->get_label()));
            }

            $mc = new org_openpsa_relatedto_collector($this->invoice->guid, 'org_openpsa_projects_task_dba');
            $tasks = $mc->get_related_objects();

            // Close "Send invoice" task
            foreach ($tasks as $task)
            {
                if (org_openpsa_projects_workflow::complete($task))
                {
                    midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('marked task "%s" finished'), $task->title));
                }
            }
        }
        return $this->reply(true, sprintf($this->_l10n->get('marked invoice %s sent'), $this->invoice->get_label()));
    }
}
