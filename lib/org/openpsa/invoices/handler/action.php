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

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_process($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        if (   empty($_POST['id'])
            || empty($_POST['action']))
        {
            throw new midcom_error('Incomplete POST data');
        }

        $invoice = new org_openpsa_invoices_invoice_dba((int) $_POST['id']);
        $invoice->require_do('midgard:update');

        if (   $_POST['action'] === '_handler_process'
            || !method_exists($this, $_POST['action']))
        {
            debug_add("The action " . $_POST["action"] . " is unknown");
            throw new midcom_error_notfound('Invalid operation');
        }
        $success = $this->{$_POST['action']}($invoice);

        $this->message['title'] = $this->_l10n->get($this->_component);
        $this->message['type'] = $success ? 'info' : 'error';

        if (!empty($_POST['relocate']))
        {
            midcom::get()->uimessages->add($this->message['title'], $this->message['message'], $this->message['type']);
            return new midcom_response_relocate('');
        }

        $result = array
        (
            'success' => $success,
            'action' => $this->_master->render_invoice_actions($invoice),
            'due' => strftime('%Y-%m-%d', $invoice->due),
            'new_status' => $invoice->get_status(),
            'message' => $this->message
        );
        return new midcom_response_json($result);
    }

    private function create_cancelation(org_openpsa_invoices_invoice_dba $invoice)
    {
        // can be canceled?
        if (!$invoice->is_cancelable())
        {
            $this->message['message'] = sprintf($this->_l10n->get('cancelation for invoice %s already exists'), $invoice->get_label());
            return false;
        }

        // process
        $cancelation_invoice = new org_openpsa_invoices_invoice_dba();
        $cancelation_invoice->customerContact = $invoice->customerContact;
        $cancelation_invoice->customer = $invoice->customer;
        $cancelation_invoice->number = $cancelation_invoice->generate_invoice_number();
        $cancelation_invoice->sum = $invoice->sum * (-1);
        $cancelation_invoice->vat = $invoice->vat;

        if (!$invoice->sent)
        {
            $invoice->sent = time();
            // if original wasn't sent, we probably don't need to send cancelation
            $cancelation_invoice->sent = time();
        }
        if (!$invoice->paid)
        {
            $invoice->paid = time();
            // if original wasn't paid, we probably don't need to pay cancelation
            $cancelation_invoice->paid = time();
        }

        if (!$cancelation_invoice->create())
        {
            $this->message['message'] = sprintf($this->_l10n->get('could not create cancelation for invoice %s'), $invoice->get_label());
            return false;
        }

        // add invoice item(s) to cancelation invoice
        // we need to copy each original item and cancel it
        $items = $invoice->get_invoice_items();
        $count = 1;
        foreach ($items as $item)
        {
            $cancelation_item = new org_openpsa_invoices_invoice_item_dba();
            $cancelation_item->invoice = $cancelation_invoice->id;
            $cancelation_item->deliverable = $item->deliverable;
            $cancelation_item->task = $item->task;
            $cancelation_item->description = sprintf($this->_l10n->get('cancelation for invoice %s, item %s'), $invoice->number, $count);
            $cancelation_item->units = $item->units;
            $cancelation_item->pricePerUnit = $item->pricePerUnit * (-1);

            if (!$cancelation_item->create())
            {
                // cleanup
                $cancelation_invoice->delete();
                $this->message['message'] = sprintf($this->_l10n->get('could not create item for cancelation invoice %s'), $cancelation_invoice->get_label());
                return false;
            }
            $count++;
        }

        $invoice->cancelationInvoice = $cancelation_invoice->id;
        if (!$invoice->update())
        {
            // cleanup
            $cancelation_invoice->delete();
            $this->message['message'] = sprintf($this->_l10n->get('could not update invoice %s'), $invoice->get_label());
            return false;
        }

        // redirect to invoice page
        midcom::get()->relocate("invoice/" . $cancelation_invoice->guid . "/");
    }

    private function send_by_mail(org_openpsa_invoices_invoice_dba $invoice)
    {
        $customerCard = org_openpsa_widgets_contact::get($invoice->customerContact);
        $contactDetails = $customerCard->contact_details;
        $invoice_label = $invoice->get_label();

        // check if we got an invoice date..
        if (!$invoice->date)
        {
            $invoice->date = time();
            $invoice->update();
        }
        $invoice_date = $this->_l10n->get_formatter()->date($invoice->date);

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
            $this->message['message'] = sprintf($this->_l10n->get('unable to deliver mail: %s'), $mail->get_error_message());
            return false;
        }
        $invoice->set_parameter($this->_component, 'sent_by_mail', time());
        return $this->mark_sent($invoice);
    }

    /**
     * Mark invoice as paid
     *
     * @todo maybe move it to invoice-class ?
     * @param org_openpsa_invoices_invoice_dba $invoice contains invoice
     */
    private function mark_paid(org_openpsa_invoices_invoice_dba $invoice)
    {
        if (!$invoice->paid)
        {
            $invoice->paid = time();
            if (!$invoice->update())
            {
                $this->message['message'] = sprintf($this->_l10n->get('could not mark invoice %s paid'), $invoice->get_label());
                return false;
            }
            $this->message['message'] = sprintf($this->_l10n->get('marked invoice %s paid'), $invoice->get_label());
        }
        return true;
    }

    /**
     * Mark invoice as sent
     *
     * @todo maybe move it to invoice-class ?
     * @param org_openpsa_invoices_invoice_dba $invoice contains invoice
     */
    private function mark_sent(org_openpsa_invoices_invoice_dba $invoice)
    {
        if (!$invoice->sent)
        {
            $invoice->sent = time();

            if (!$invoice->update())
            {
                $this->message['message'] = sprintf($this->_l10n->get('could not mark invoice %s paid'), $invoice->get_label());
                return false;
            }
            $this->message['message'] = sprintf($this->_l10n->get('marked invoice %s sent'), $invoice->get_label());

            $mc = new org_openpsa_relatedto_collector($invoice->guid, 'org_openpsa_projects_task_dba');
            $tasks = $mc->get_related_objects();

            // Close "Send invoice" task
            foreach ($tasks as $task)
            {
                if (org_openpsa_projects_workflow::complete($task))
                {
                    midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked task "%s" finished'), $task->title));
                }
            }
        }
        return true;
    }
}
