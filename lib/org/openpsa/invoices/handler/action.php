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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_mark_sent($handler_id, array $args, array &$data)
    {
        $this->_prepare_action($args);

        if (!$this->_object->sent)
        {
            $this->_object->sent = time();
            $this->_object->update();

            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked invoice "%s" sent'), $this->_object->get_label()), 'ok');

            $mc = new org_openpsa_relatedto_collector($this->_object->guid, 'org_openpsa_projects_task_dba');
            $tasks = $mc->get_related_objects();

            // Close "Send invoice" task
            foreach ($tasks as $task)
            {
                if (org_openpsa_projects_workflow::complete($task))
                {
                    $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked task "%s" finished'), $task->title), 'ok');
                }
            }
        }

        if (isset($args["no_redirect"]))
        {
            return true;
        }
        else
        {
            $this->_relocate();
        }
    }

    /**
     * This function will not only mark the invoice, but
     * actually send the mail as well
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_mark_sent_per_mail($handler_id, array $args, array &$data)
    {
        $args["no_redirect"] = true;

        // remove prepare and mark instead when everything works here
        $this->_prepare_action($args);
        // $this->_handler_mark_sent($handler_id, $args, &$data);

        // load mailer component
        $_MIDCOM->componentloader->load('org.openpsa.mail');
        $mail = new org_openpsa_mail();

        $customerCard = org_openpsa_contactwidget::get($this->_object->customerContact);
        $contactDetails = $customerCard->contact_details;
        $invoice_label = $this->_object->get_label();

        // generate pdf, only if not existing yet
        $pdf_files = org_openpsa_helpers::get_attachment_urls($this->_object, "pdf_file");
        if (count($pdf_files) == 0)
        {
            $_MIDCOM->skip_page_style = true;
            $this->_object->render_and_attach_pdf();
        }

        // define replacements for subject / body
        $replacements = array(
            "__INVOICE_LABEL__" => $invoice_label,
            "__FIRSTNAME__" => $contactDetails["firstname"],
            "__LASTNAME__" => $contactDetails["lastname"]
        );

        $mail->to = $contactDetails["email"];
        $mail->from = $this->_config->get('invoice_mail_from_address');

        $mail->subject = strtr($this->_config->get('invoice_mail_title'), $replacements);
        $mail->body = strtr($this->_config->get('invoice_mail_body'), $replacements);

        // attach pdf to mail
        if ($mail->can_attach())
        {
            $pdf_files = org_openpsa_helpers::get_attachment_urls($this->_object, "pdf_file");

            if (count($pdf_files) > 0)
            {
                foreach ($pdf_files as $guid => $url)
                {
                    $att = array();
                    $att['name'] = basename($url);
                    $att['mimetype'] = "application/pdf";

                    $fp = fopen($url, "r");
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
                    fclose($fp);
                    debug_add("adding attachment '{$att['name']}' to attachments array of invoice mail");
                    $mail->attachments[] = $att;
                    unset($att);
                }
            }
        }

        // send mail
        $ret = $mail->send();

        if (!$ret)
        {
            throw new midcom_error("Unable to deliver mail.");
        }
        else
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked invoice "%s" sent per mail'), $this->_object->get_label()), 'ok');
        }

        // relocate
        $this->_relocate();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_mark_paid($handler_id, array $args, array &$data)
    {
        $this->_prepare_action($args);

        if (!$this->_object->paid)
        {
            $this->_object->paid = time();
            $this->_object->update();

            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('marked invoice "%s" paid'), $this->_object->get_label()), 'ok');
        }

        $this->_relocate();
    }

    /**
     * Helper that prepares the action
     *
     * @return boolean Indicating success
     */
    private function _prepare_action(&$args)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            throw new midcom_error_forbidden('Only POST requests are allowed here.');
        }

        $_MIDCOM->auth->require_valid_user();

        $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);
        $this->_object->require_do('midgard:update');
    }

    /**
     * Helper that redirects after the action completed
     */
    private function _relocate()
    {
        if (isset($_GET['org_openpsa_invoices_redirect']))
        {
            $_MIDCOM->relocate($_GET['org_openpsa_invoices_redirect']);
            // This will exit
        }
        else
        {
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $_MIDCOM->relocate("{$prefix}invoice/{$this->_object->guid}/");
            // This will exit
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
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
        $this->_request_data['grid'] = new org_openpsa_core_grid_widget('invoice_items', 'local');

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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_itemedit($handler_id, array $args, array &$data)
    {
        $this->_verify_post_data();

        $invoice = new org_openpsa_invoices_invoice_dba($args[0]);

        $_MIDCOM->skip_page_style = true;

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
                $item->units = (float) $_POST['quantity'];
                $item->pricePerUnit = (float) $_POST['price'];
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
                    throw new midcom_error('Failed to update item: ' . midcom_connection::get_error_string());
                }

                break;
            default:
                throw new midcom_error('Invalid operation "' . $_POST['oper'] . '"');
        }
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_recalculation($handler_id, array $args, array &$data)
    {
        $this->_object = new org_openpsa_invoices_invoice_dba($args[0]);
        $relocate = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "invoice/items/" . $this->_object->guid . "/";

        $this->_object->_recalculate_invoice_items();

        $_MIDCOM->relocate($relocate);
    }

    private function _prepare_output()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/list.css');

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
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_object),
            )
        );

        $this->_master->add_next_previous($this->_object, $this->_view_toolbar, 'invoice/items/');

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.invoices/invoice_item.js');
    }
}
?>