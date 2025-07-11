<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\Request;
use midcom\datamanager\controller;
use midcom\datamanager\datamanager;

/**
 * Invoice action handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_invoice_action extends midcom_baseclasses_components_handler
{
    use org_openpsa_invoices_handler;

    private org_openpsa_invoices_invoice_dba $invoice;

    private string $old_status;

    private ?midcom_core_dbaobject $mail_recipient = null;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $this->invoice = new org_openpsa_invoices_invoice_dba((int) $_POST['id']);
            $this->invoice->require_do('midgard:update');
            $this->old_status = $this->invoice->get_status();
        }
    }

    private function reply(bool $success, string $message) : Response
    {
        $message = [
            'title' => $this->_l10n->get($this->_component),
            'type' => $success ? 'info' : 'error',
            'message' => $message
        ];

        if (!empty($_POST['relocate'])) {
            midcom::get()->uimessages->add($message['title'], $message['message'], $message['type']);
            return new midcom_response_relocate($this->router->generate('invoice', ['guid' => $this->invoice->guid]));
        }

        return new JsonResponse([
            'success' => $success,
            'action' => $this->render_invoice_actions($this->invoice),
            'new_status' => $this->invoice->get_status(),
            'old_status' => $this->old_status,
            'messages' => [$message],
            'updated' => [
                ['due', date('Y-m-d', $this->invoice->due)]
            ]
        ]);
    }

    public function _handler_create_cancelation()
    {
        // can be canceled?
        if (!$this->invoice->is_cancelable()) {
            return $this->reply(false, sprintf($this->_l10n->get('cancelation for invoice %s already exists'), $this->invoice->get_label()));
        }

        // process
        $cancelation_invoice = new org_openpsa_invoices_invoice_dba();
        $cancelation_invoice->customerContact = $this->invoice->customerContact;
        $cancelation_invoice->customer = $this->invoice->customer;
        $cancelation_invoice->number = $cancelation_invoice->generate_invoice_number();
        $cancelation_invoice->sum = $this->invoice->sum * (-1);
        $cancelation_invoice->vat = $this->invoice->vat;

        if (!$this->invoice->sent) {
            $this->invoice->sent = time();
            // if original wasn't sent, we probably don't need to send cancelation
            $cancelation_invoice->sent = time();
        }
        if (!$this->invoice->paid) {
            $this->invoice->paid = time();
            // if original wasn't paid, we probably don't need to pay cancelation
            $cancelation_invoice->paid = time();
        }

        if (!$cancelation_invoice->create()) {
            return $this->reply(false, sprintf($this->_l10n->get('could not create cancelation for invoice %s'), $this->invoice->get_label()));
        }

        // add invoice item(s) to cancelation invoice
        // we need to copy each original item and cancel it
        $items = $this->invoice->get_invoice_items();
        $count = 1;
        foreach ($items as $item) {
            $cancelation_item = new org_openpsa_invoices_invoice_item_dba();
            $cancelation_item->invoice = $cancelation_invoice->id;
            $cancelation_item->deliverable = $item->deliverable;
            $cancelation_item->task = $item->task;
            $cancelation_item->description = sprintf($this->_l10n->get('cancelation for invoice %s, item %s'), $this->invoice->number, $count);
            $cancelation_item->units = $item->units;
            $cancelation_item->pricePerUnit = $item->pricePerUnit * (-1);

            if (!$cancelation_item->create()) {
                // cleanup
                $cancelation_invoice->delete();
                return $this->reply(false, sprintf($this->_l10n->get('could not create item for cancelation invoice %s'), $cancelation_invoice->get_label()));
            }
            $count++;
        }

        $this->invoice->cancelationInvoice = $cancelation_invoice->id;
        if (!$this->invoice->update()) {
            // cleanup
            $cancelation_invoice->delete();
            return $this->reply(false, sprintf($this->_l10n->get('could not update invoice %s'), $this->invoice->get_label()));
        }

        return new midcom_response_relocate($this->router->generate('invoice', ['guid' => $cancelation_invoice->guid]));
    }

    public function _handler_create_pdf()
    {
        $pdf_helper = new org_openpsa_invoices_invoice_pdf($this->invoice);
        try {
            $pdf_helper->render_and_attach();
            return $this->reply(true, $this->_l10n->get('pdf created'));
        } catch (midcom_error $e) {
            return $this->reply(false, $this->_l10n->get('pdf creation failed') . ': ' . $e->getMessage());
        }
    }

    public function _handler_create_pdf_reminder()
    {
        $pdf_helper = new org_openpsa_invoices_invoice_pdf($this->invoice);
        try {
            $pdf_helper->render_and_attach('reminder');
            return $this->reply(true, $this->_l10n->get('reminder pdf created'));
        } catch (midcom_error $e) {
            return $this->reply(false, $this->_l10n->get('reminder pdf creation failed') . ': ' . $e->getMessage());
        }
    }    

    private function load_send_mail_controller() : controller
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_send_mail'));
        $dm = new datamanager($schemadb);
        
        $subject = null;
        $message = null;

        $customer = $this->invoice->get_customer();
        if ($customer) {
            $message = $customer->get_parameter('org.openpsa.invoices', 'last_invoice_mail_message');
            $subject = $customer->get_parameter('org.openpsa.invoices', 'last_invoice_mail_subject');
        }
        $this->mail_recipient = $customer;

        $dm->set_defaults([
            'subject' => $subject ?: $this->_l10n->get('invoice_mail_title_default'),
            'message' => $message ?: $this->_l10n->get('invoice_mail_body_default')
        ]);

        return $dm->get_controller();
    }

    public function _handler_send_by_mail(Request $request, string $guid)
    {
        midcom::get()->head->set_pagetitle($this->_l10n->get('send_by_mail'));
        $this->invoice = new org_openpsa_invoices_invoice_dba($guid);
        $this->invoice->require_do('midgard:update');

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_send_mail_controller(),
            'save_callback' => $this->save_callback(...),
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        $this->old_status = $this->invoice->get_status();
        
        $data = $controller->get_datamanager()->get_content_raw();
       
        if ($this->mail_recipient) {
            $this->mail_recipient->set_parameter('org.openpsa.invoices', 'last_invoice_mail_message', $data['message']);
            $this->mail_recipient->set_parameter('org.openpsa.invoices', 'last_invoice_mail_subject', $data['subject']);
        }

        $customerCard = org_openpsa_widgets_contact::get($this->invoice->customerContact);
        $contactDetails = $customerCard->contact_details;
        $invoice_label = $this->invoice->get_label();

        // check if we got an invoice date..
        if (!$this->invoice->date) {
            $this->invoice->date = time();
            $this->invoice->update();
        }
        $invoice_date = $this->_l10n->get_formatter()->date($this->invoice->date);

        $pdf_helper = new org_openpsa_invoices_invoice_pdf($this->invoice);
        $attachment = $pdf_helper->get_attachment(true);

        $mail = new org_openpsa_mail();
        $mail->attachments[] = [
            'name' => $attachment->name,
            'mimetype' => "application/pdf",
            'content' => $attachment->read()
        ];

        // define replacements for subject / body
        $mail->parameters = [
            "INVOICE_LABEL" => $invoice_label,
            "INVOICE_DATE" => $invoice_date,
            "FIRSTNAME" => $contactDetails["firstname"],
            "LASTNAME" => $contactDetails["lastname"]
        ];

        $mail->to = $contactDetails["email"];
        $mail->from = $this->_config->get('invoice_mail_from_address');
        $mail->subject = $data['subject'];
        $mail->body = $data['message'];

        if ($this->_config->get('invoice_mail_bcc')) {
            $mail->bcc = $this->_config->get('invoice_mail_bcc');
        }

        $_POST['relocate'] = '1';

        if (!$mail->send()) {
            $response = $this->reply(false, sprintf($this->_l10n->get('unable to deliver mail: %s'), $mail->get_error_message()));
            return $response->getTargetUrl();
        }
        $this->invoice->set_parameter($this->_component, 'sent_by_mail', time());

        $response = $this->_handler_mark_sent();
        return $response->getTargetUrl();
    }

    public function _handler_mark_paid()
    {
        if (!$this->invoice->paid) {
            $this->invoice->paid = time();
            if (!$this->invoice->update()) {
                return $this->reply(false, sprintf($this->_l10n->get('could not mark invoice %s paid'), $this->invoice->get_label()));
            }
        }
        return $this->reply(true, sprintf($this->_l10n->get('marked invoice %s paid'), $this->invoice->get_label()));
    }

    public function _handler_mark_sent()
    {
        if (!$this->invoice->sent) {
            $this->invoice->sent = time();

            if (!$this->invoice->update()) {
                return $this->reply(false, sprintf($this->_l10n->get('could not mark invoice %s paid'), $this->invoice->get_label()));
            }

            $mc = new org_openpsa_relatedto_collector($this->invoice->guid, org_openpsa_projects_task_dba::class);
            $tasks = $mc->get_related_objects();

            // Close "Send invoice" task
            foreach ($tasks as $task) {
                if (org_openpsa_projects_workflow::complete($task)) {
                    midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('marked task "%s" finished'), $task->title));
                }
            }
        }
        return $this->reply(true, sprintf($this->_l10n->get('marked invoice %s sent'), $this->invoice->get_label()));
    }
}
