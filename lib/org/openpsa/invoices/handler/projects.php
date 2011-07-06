<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * invoice projects invoicing handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_projects extends midcom_baseclasses_components_handler
{
    /**
     * The array of tasks.
     *
     * @var Array
     */
    private $_tasks = array();

    /**
     * The customer cache.
     *
     * @var Array
     */
    private $_customers = array();

    private function _generate_invoice()
    {
        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice->customer = (int) $_POST['org_openpsa_invoices_invoice_customer'];
        $invoice->number = org_openpsa_invoices_invoice_dba::generate_invoice_number();
        $invoice->owner = midcom_connection::get_user();

        // Fill VAT value
        $vat_array = explode(',', $this->_config->get('vat_percentages'));
        if (   is_array($vat_array)
            && count($vat_array) > 0)
        {
            $invoice->vat = (int) $vat_array[0];
        }

        $invoice->description = '';

        $invoice_items = array();
        foreach ($_POST['org_openpsa_invoices_invoice_tasks'] as $task_id => $invoiceable)
        {
            if ($invoiceable)
            {
                $task = $this->_tasks[$task_id];

                //instance the invoice_items
                $invoice_items[$task_id] = new org_openpsa_invoices_invoice_item_dba();
                $invoice_items[$task_id]->task = $task_id;
                $invoice_items[$task_id]->description = $task->title;
                $invoice_items[$task_id]->pricePerUnit = (float) $_POST['org_openpsa_invoices_invoice_tasks_price'][$task_id];
                $invoice_items[$task_id]->units = (float) $_POST['org_openpsa_invoices_invoice_tasks_units'][$task_id];
            }
        }

        if ($invoice->create())
        {
            // create invoice_items
            foreach ($invoice_items as $invoice_item)
            {
                $invoice_item->invoice = $invoice->id;
                $invoice_item->create();

                $task = $this->_tasks[$invoice_item->task];

                // Connect invoice to the tasks involved
                org_openpsa_projects_workflow::mark_invoiced($task, $invoice);
            }

            // Generate "Send invoice" task
            $invoice_sender_guid = $this->_config->get('invoice_sender');
            if (!empty($invoice_sender_guid))
            {
                $invoice->generate_invoicing_task($invoice_sender_guid);
            }

            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), sprintf($this->_l10n->get('invoice "%s" created'), $invoice->get_label()), 'ok');

            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $_MIDCOM->relocate("{$prefix}invoice/edit/{$invoice->guid}/");
            // This will exit
        }
        else
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.invoices'), $this->_l10n->get('failed to create invoice, reason ') . midcom_connection::get_error_string(), 'error');
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_uninvoiced($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $_MIDCOM->auth->require_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba');

        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('status', '>=', org_openpsa_projects_task_status_dba::COMPLETED);

        //Load component here already to get the needed constants
        if ($_MIDCOM->componentloader->load_graceful('org.openpsa.sales'))
        {
            $qb->begin_group('OR');
                $qb->add_constraint('invoiceableHours', '>', 0);
                $qb->begin_group('AND');
                    $qb->add_constraint('agreement.invoiceByActualUnits', '=', false);
                    $qb->add_constraint('agreement.state', '=', org_openpsa_sales_salesproject_deliverable_dba::STATUS_DELIVERED);
                    $qb->add_constraint('agreement.price', '>', 0);
                $qb->end_group();
            $qb->end_group();
        }
        else
        {
            $qb->add_constraint('invoiceableHours', '>', 0);
        }
        $tasks = $qb->execute();

        foreach ($tasks as $task)
        {
            $this->_tasks[$task->id] = $task;

            if (!array_key_exists($task->customer, $this->_customers))
            {
                $this->_customers[$task->customer] = array();
            }

            $this->_customers[$task->customer][$task->id] =& $this->_tasks[$task->id];
        }

        // Check if we're sending an invoice here
        if (array_key_exists('org_openpsa_invoices_invoice', $_POST))
        {
            $this->_generate_invoice();
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        $title = $this->_l10n->get('project invoicing');

        $_MIDCOM->set_pagetitle($title);

        $this->add_breadcrumb("", $title);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_uninvoiced($handler_id, array &$data)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();

        $data['projects_url'] = $siteconfig->get_node_full_url('org.openpsa.projects');

        midcom_show_style('show-projects-header');
        foreach ($this->_customers as $customer_id => $tasks)
        {
            $data['customer'] = $customer_id;
            try
            {
                $customer = org_openpsa_contacts_group_dba::get_cached($customer_id);
                $data['customer_label'] = $customer->official;
                $data['disabled'] = '';
            }
            catch (midcom_error $e)
            {
                $data['customer_label'] = $this->_l10n->get('no customer');
                $data['disabled'] = ' disabled="disabled"';
            }
            midcom_show_style('show-projects-customer-header');

            $class = "even";
            foreach ($tasks as $task)
            {
                $data['task'] = $task;

                if ($class == "even")
                {
                    $class = "";
                }
                else
                {
                    $class = "even";
                }
                $data['class'] = $class;

                if (   class_exists('org_openpsa_sales_salesproject_deliverable_dba')
                    && $task->agreement)
                {
                    $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($task->agreement);
                    $deliverable->calculate_price(false);
                    $data['default_price'] = $deliverable->pricePerUnit;
                    $data['invoiceable_hours'] = $task->invoiceableHours;
                }
                else if ($this->_config->get('default_hourly_price'))
                {
                    $data['default_price'] = $this->_config->get('default_hourly_price');
                    $data['invoiceable_hours'] = $task->invoiceableHours;
                }
                else
                {
                    $data['default_price'] = '';
                }
                midcom_show_style('show-projects-customer-task');
            }
            midcom_show_style('show-projects-customer-footer');
        }
        midcom_show_style('show-projects-footer');
    }
}
?>