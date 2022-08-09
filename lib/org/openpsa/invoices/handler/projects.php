<?php
/**
 * @package org.openpsa.invoices
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * invoice projects invoicing handler
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_handler_projects extends midcom_baseclasses_components_handler
{
    use org_openpsa_invoices_handler;

    /**
     * @var org_openpsa_projects_task_dba[]
     */
    private $_tasks = [];

    /**
     * @var array
     */
    private $_customers = [];

    private function _generate_invoice(ParameterBag $post) : ?org_openpsa_invoices_invoice_dba
    {
        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice->customer = $post->getInt('org_openpsa_invoices_invoice_customer');
        $invoice->number = $invoice->generate_invoice_number();
        $invoice->owner = midcom_connection::get_user();
        $invoice->vat = $invoice->get_default('vat');
        $invoice->description = $invoice->get_default('remarks');

        if (!$invoice->create()) {
            return null;
        }

        // create invoice_items
        $ids = array_keys(array_filter($post->all('org_openpsa_invoices_invoice_tasks')));
        foreach ($ids as $task_id) {
            $task = $this->_tasks[$task_id];

            //instance the invoice_items
            $item = new org_openpsa_invoices_invoice_item_dba();
            $item->task = $task_id;
            try {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($task->get_agreement());
                $item->deliverable = $deliverable->id;
            } catch (midcom_error $e) {
                $e->log();
            }
            $item->invoice = $invoice->id;
            $item->description = $task->title;
            $item->pricePerUnit = (float) $post->all('org_openpsa_invoices_invoice_tasks_price')[$task_id];
            $item->units = (float) $post->all('org_openpsa_invoices_invoice_tasks_units')[$task_id];
            $item->create();

            // Connect invoice to the tasks involved
            org_openpsa_expenses_hour_report_dba::mark_invoiced($task, $invoice);
        }
        return $invoice;
    }

    public function _handler_uninvoiced(Request $request)
    {
        midcom::get()->auth->require_valid_user();
        midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_invoices_invoice_dba::class);

        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('status', '>=', org_openpsa_projects_task_status_dba::COMPLETED);

        $qb->begin_group('OR');
        $qb->add_constraint('invoiceableHours', '>', 0);
        $qb->begin_group('AND');
        $qb->add_constraint('agreement.invoiceByActualUnits', '=', false);
        $qb->add_constraint('agreement.state', '=', org_openpsa_sales_salesproject_deliverable_dba::STATE_DELIVERED);
        $qb->add_constraint('agreement.price', '>', 0);
        $qb->end_group();
        $qb->end_group();

        foreach ($qb->execute() as $task) {
            $this->_tasks[$task->id] = $task;

            if (!array_key_exists($task->customer, $this->_customers)) {
                $this->_customers[$task->customer] = [];
            }

            $this->_customers[$task->customer][$task->id] = $this->_tasks[$task->id];
        }

        // Check if we're sending an invoice here
        if ($request->request->has('org_openpsa_invoices_invoice')) {
            if ($invoice = $this->_generate_invoice($request->request)) {
                return new midcom_response_relocate($this->router->generate('invoice', ['guid' => $invoice->guid]));
            }
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $this->_l10n->get('failed to create invoice, reason') . midcom_connection::get_error_string(), 'error');
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.invoices/invoices.js');

        $this->set_active_leaf($this->_topic->id . ':projects');
        midcom::get()->head->set_pagetitle($this->_l10n->get('project invoicing'));

        $this->prepare_toolbar();
    }

    public function _show_uninvoiced(string $handler_id, array &$data)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $data['projects_url'] = $siteconfig->get_node_full_url('org.openpsa.projects');

        midcom_show_style('show-projects-header');
        foreach ($this->_customers as $customer_id => $tasks) {
            $data['customer'] = $customer_id;
            try {
                $customer = org_openpsa_contacts_group_dba::get_cached($customer_id);
                $data['customer_label'] = $customer->official;
                $data['disabled'] = '';
            } catch (midcom_error $e) {
                $data['customer_label'] = $this->_l10n->get('no customer');
                $data['disabled'] = ' disabled="disabled"';
            }
            midcom_show_style('show-projects-customer-header');

            $class = "even";
            foreach ($tasks as $task) {
                $data['task'] = $task;

                $class = ($class == "even") ? '' : 'even';
                $data['class'] = $class;
                $data['reported_hours'] = $task->reportedHours;
                try {
                    $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($task->get_agreement());
                    $deliverable->calculate_price(false);
                    $data['default_price'] = $deliverable->pricePerUnit;

                    if ($deliverable->invoiceByActualUnits) {
                        $data['invoiceable_units'] = $task->invoiceableHours;
                    } else {
                        $data['invoiceable_units'] = $task->plannedHours;
                    }
                } catch (midcom_error $e) {
                    $e->log();
                    if ($this->_config->get('default_hourly_price')) {
                        $data['default_price'] = $this->_config->get('default_hourly_price');
                        $data['invoiceable_units'] = $task->invoiceableHours;
                    } else {
                        $data['default_price'] = 0;
                        $data['invoiceable_units'] = 0;
                    }
                }
                midcom_show_style('show-projects-customer-task');
            }
            midcom_show_style('show-projects-customer-footer');
        }
        midcom_show_style('show-projects-footer');
    }
}
