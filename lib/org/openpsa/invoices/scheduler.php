<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class to process subscription invoicing
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_scheduler extends midcom_baseclasses_components_purecode
{
    /**
     * The deliverable we're processing
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable = null;

    function __construct($deliverable)
    {
        $this->_deliverable = $deliverable;
    }

    /**
     * Initiates a new subscription cycle and registers a midcom.services.at call for the next cycle.
     *
     * The subscription cycles rely on midcom.services.at. I'm not sure if it is wise to rely on it for such
     * a totally mission critical part of OpenPSA. Some safeguards might be wise to add.
     */
    function run_cycle($cycle_number, $send_invoice = true)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        if (time() < $this->_deliverable->start)
        {
            debug_add('Subscription hasn\'t started yet, register the start-up event to $start');
            debug_pop();
            return $this->_create_at_entry($cycle_number, $this->_deliverable->start);
        }

        debug_add('Running cycle ' . $cycle_number . ' for deliverable "' . $this->_deliverable->title . '"');

        if ($cycle_number == 1)
        {
            $this_cycle_start = $this->_deliverable->start;
        }
        else
        {
            $this_cycle_start = time();
        }

        $next_cycle_start = $this->calculate_cycle_next($this_cycle_start);

        $product = org_openpsa_products_product_dba::get_cached($this->_deliverable->product);

        // Recalculate price to catch possible unit changes
        $this->_deliverable->calculate_price();
        $this_cycle_amount = $this->_deliverable->price;

        if ($this->_deliverable->state < ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED)
        {
            $this->_deliverable->state = ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED;
        }

        $this->_deliverable->invoiced = $this->_deliverable->invoiced + $this_cycle_amount;
        $this->_deliverable->update();

        if ($send_invoice)
        {
            if ($this_cycle_amount > 0)
            {
                $this->_invoice($this_cycle_amount, sprintf('%s %s', $this->_deliverable->title, $cycle_number), $cycle_number);
            }
            else
            {
                debug_add('Invoice sum 0, skipping invoice creation');
            }
        }

        $tasks_completed = array();
        $tasks_not_completed = array();

        if ($product->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE)
        {
            // Close previous task(s)
            $last_task = null;
            $new_task = null;

            $task_qb = org_openpsa_projects_task_dba::new_query_builder();
            $task_qb->add_constraint('agreement', '=', $this->_deliverable->id);
            $task_qb->add_constraint('status', '<', ORG_OPENPSA_TASKSTATUS_CLOSED);
            $tasks = $task_qb->execute();

            foreach ($tasks as $task)
            {
                $stat = org_openpsa_projects_workflow::complete($task, sprintf($_MIDCOM->i18n->get_string('completed by subscription %s', 'org.openpsa.sales'), $cycle_number));
                if ($stat)
                {
                    $tasks_completed[] = $task;
                }
                else
                {
                    $tasks_not_completed[] = $task;
                }
                $last_task = $task;
            }

            // Create task for the duration of this cycle
            $new_task = $this->create_task($this_cycle_start, $next_cycle_start - 1, sprintf('%s %s', $this->_deliverable->title, $cycle_number), $last_task);
        }

        // TODO: Warehouse management: create new order

        if (   $this->_deliverable->end < $next_cycle_start
            && $this->_deliverable->end != 0)
        {
            debug_add('Do not register next cycle, the contract ends before');
            debug_pop();
            return true;
        }

        if ($this->_create_at_entry($cycle_number + 1, $next_cycle_start))
        {
            $this->_notify_owner($cycle_number, $next_cycle_start, $this_cycle_amount, $tasks_completed, $tasks_not_completed);
            debug_pop();
            return true;
        }
        else
        {
            debug_pop();
            return false;
        }
    }

    private function _create_at_entry($cycle_number, $start)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $_MIDCOM->load_library('midcom.services.at');

        $args = array
        (
            'deliverable' => $this->_deliverable->guid,
            'cycle'       => $cycle_number,
        );
        $at_entry = new midcom_services_at_entry();
        $at_entry->start = $start;
        $at_entry->component = 'org.openpsa.sales';
        $at_entry->method = 'new_subscription_cycle';
        $at_entry->arguments = $args;

        if ($at_entry->create())
        {
            debug_add('AT entry for cycle ' . $cycle_number . ' created');
            org_openpsa_relatedto_plugin::create($at_entry, 'midcom.services.at', $this->_deliverable, 'org.openpsa.sales');
            debug_pop();
            return true;
        }
        else
        {
            debug_add('AT registration failed, last midgard error was: ' . midcom_application::get_error_string(), MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }
    }

    /**
     * Generate an invoice from the deliverable.
     *
     * Creates a new, unsent org.openpsa.invoices item
     * and adds a relation between it and the deliverable.
     */
    private function _invoice($sum, $description, $cycle_number = null)
    {
        $invoice = $this->_probe_invoice($cycle_number);

        $invoice->due = ($invoice->get_default_due() * 3600 * 24) + time();

        $invoice->description = $invoice->description . "\n\n" . $description;
        $invoice->sum = $invoice->sum + $sum;

        if (!$invoice->update())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                                     "The invoice for this cycle could not be saved. Last Midgard error was: " . midcom_application::get_error_string());
            // This will exit.
        }

        // TODO: Create invoicing task if assignee is defined

        // Mark the tasks (and hour reports) related to this agreement as invoiced
        $tasks = array();

        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('agreement', '=', $this->_deliverable->id);

        if ($this->_deliverable->invoiceByActualUnits)
        {
            $qb->add_constraint('invoiceableHours', '>', 0);
            $tasks = $qb->execute();
        }
        else
        {
            /* This is where it gets ugly: Since there is no direct way to find out if a task has already
             * been invoiced, we have to deduce this by looking at existing relatedtos. This is of course quite
             * inefficient, but necessary to avoid creating ever-growing numbers of relatedtos (see #1893)
             */
            $guids = array();
            $task_mc = org_openpsa_projects_task_dba::new_collector('agreement', $this->_deliverable->id);
            $task_mc->execute();

            foreach ($task_mc->list_keys() as $guid => $empty)
            {
                $related_mc = new org_openpsa_relatedto_collector($guid, 'org_openpsa_invoices_invoice_dba');
                $result = $related_mc->get_related_guids();
                if (sizeof($result) > 0)
                {
                    continue;
                }
                $guids[] = $guid;
            }
            if (sizeof($guids) > 0)
            {
                $qb->add_constraint('guid', 'IN', $guids);
                $tasks = $qb->execute();
            }
            else
            {
                $tasks = array();
            }
        }

        foreach ($tasks as $task)
        {
            org_openpsa_projects_workflow::mark_invoiced($task, $invoice);
        }

        if (sizeof($tasks) == 0)
        {
            /*
             * Normally, the actions below would happen via mark_invoiced, but when there are no tasks,
             * we have to do this manually
             */
            org_openpsa_relatedto_plugin::create($invoice, 'org.openpsa.invoices', $this->_deliverable, 'org.openpsa.sales');

            $item = new org_openpsa_invoices_invoice_item_dba();
            $item->invoice = $invoice->id;
            $item->description = $description;

            if (   $this->_deliverable->invoiceByActualUnits
                || $this->_deliverable->plannedUnits == 0)
            {
                $item->units = $this->_deliverable->units;
                $item->pricePerUnit = $this->_deliverable->pricePerUnit;
            }
            else
            {
                $item->units = $this->_deliverable->plannedUnits;
                $item->pricePerUnit = $this->_deliverable->pricePerUnit;
            }
            $item->create();
        }
    }

    /**
     * Helper function that tries to locate unsent invoices for deliverables in the same salesproject
     *
     * Example use case: A support contract with multiple hourly rates (defined
     * as deliverables) for different types of work. Instead of sending the customer
     * one invoice per hourly rate per month, one composite invoice for all fees is generated
     */
    private function _probe_invoice($cycle_number)
    {
        if (is_null($cycle_number))
        {
            //we're not working with a subscription, so better create a new invoice right away
            return $this->_create_invoice();
        }

        $deliverable_mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->_deliverable->salesproject);
        $deliverable_mc->add_constraint('state', '>', ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED);
        $deliverable_mc->add_constraint('product.delivery', '=', ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION);
        $deliverable_mc->execute();
        $deliverables = $deliverable_mc->list_keys();

        $mc = new org_openpsa_relatedto_collector(array_keys($deliverables), 'org_openpsa_invoices_invoice_dba');

        $suspects = $mc->get_related_guids();
        if (sizeof($suspects) > 0)
        {
            $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
            $qb->add_constraint('guid', 'IN', $suspects);
            $qb->add_constraint('parameter.value', '=', $cycle_number);
            $qb->add_constraint('sent', '=', 0);
            $results = $qb->execute();
            if (sizeof($results) == 1)
            {
                return $results[0];
            }
        }
        //Nothing or ambiguous results found, create a new invoice
        return $this->_create_invoice($cycle_number);
    }

    private function _create_invoice($cycle_number = null)
    {
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);
        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice->customer = $salesproject->customer;
        $invoice->number = org_openpsa_invoices_invoice_dba::generate_invoice_number();
        $invoice->owner = $salesproject->owner;

        $invoice->vat = $invoice->get_default_vat();
        $invoice->due = ($invoice->get_default_due() * 3600 * 24) + time();

        if ($invoice->create())
        {
            // Register the cycle number for reporting purposes
            if (!is_null($cycle_number))
            {
                $invoice->parameter('org.openpsa.sales', 'cycle_number', $cycle_number);
            }
            return $invoice;
        }
        return false;
    }

    private function _notify_owner($cycle_number, $next_run, $invoiced_sum, $tasks_completed, $tasks_not_completed, $new_task = null)
    {
        // Prepare notification to sales project owner
        $message = array();
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);
        $owner = midcom_db_person::get_cached($salesproject->owner);
        $customer = midcom_db_group::get_cached($salesproject->customer);

        if (is_null($next_run))
        {
            $next_run_label = $_MIDCOM->i18n->get_string('no more cycles', 'org.openpsa.sales');
        }
        else
        {
            $next_run_label = strftime('%x %X', $next_run);
        }

        // Title for long notifications
        $message['title'] = sprintf($_MIDCOM->i18n->get_string('subscription cycle %d closed for agreement %s (%s)', 'org.openpsa.sales'), $cycle_number, $this->_deliverable->title, $customer->official);

        // Content for long notifications
        $message['content'] = "{$message['title']}\n\n";
        $message['content'] .= $_MIDCOM->i18n->get_string('invoiced', 'org.openpsa.sales') . ": {$invoiced_sum}\n\n";

        if (count($tasks_completed) > 0)
        {
            $message['content'] .= "\n" . $_MIDCOM->i18n->get_string('tasks completed', 'org.openpsa.sales') . ":\n";

            foreach ($tasks_completed as $task)
            {
                $message['content'] .= "{$task->title}: {$task->reportedHours}h\n";
            }
        }

        if (count($tasks_not_completed) > 0)
        {
            $message['content'] .= "\n" . $_MIDCOM->i18n->get_string('tasks not completed', 'org.openpsa.sales') . ":\n";

            foreach ($tasks_not_completed as $task)
            {
                $message['content'] .= "{$task->title}: {$task->reportedHours}h\n";
            }
        }

        if ($new_task)
        {
            $message['content'] .= "\n" . $_MIDCOM->i18n->get_string('created new task', 'org.openpsa.sales') . ":\n";
            $message['content'] .= "{$task->title}\n";
        }

        $message['content'] .= "\n" . $_MIDCOM->i18n->get_string('next run', 'org.openpsa.sales') . ": {$next_run_label}\n\n";
        $message['content'] .= $_MIDCOM->i18n->get_string('salesproject', 'org.openpsa.sales') . ":\n";
        $message['content'] .= $_MIDCOM->permalinks->create_permalink($salesproject->guid);

        // Content for short notifications
        $message['abstract'] = sprintf($_MIDCOM->i18n->get_string('%s: closed subscription cycle %d for agreement %s. invoiced %d. next cycle %s', 'org.openpsa.sales'), $customer->official, $cycle_number, $this->_deliverable->title, $invoiced_sum, $next_run_label);

        // Send the message out
        $_MIDCOM->load_library('org.openpsa.notifications');
        org_openpsa_notifications::notify('org.openpsa.sales:new_subscription_cycle', $owner->guid, $message);
    }

    /**
     * Find out if there already is a project for this sales project. If not, create one.
     *
     * @return org_openpsa_projects_project $project
     */
    private function _probe_project()
    {
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);

        $mc = new org_openpsa_relatedto_collector($salesproject->guid, 'org_openpsa_projects_project');
        $mc->set_limit(1);

        $projects = $mc->get_related_objects();

        if (count($projects) > 0)
        {
            // Just pick the first
            return $projects[0];
        }

        // No project yet, try to create
        $project = new org_openpsa_projects_project();
        $project->customer = $salesproject->customer;
        $project->title = $salesproject->title;

        $schedule_object = $this;
        if ($this->_deliverable->up != 0)
        {
            $schedule_object = $this->_deliverable->get_parent();
        }
        $project->start = $schedule_object->start;
        $project->end = $schedule_object->end;

        $project->manager = $salesproject->owner;

        // TODO: If deliverable has a supplier specified, add the supplier
        // organization members as potential resources here

        // TODO: Figure out if we really want to keep this
        $project->invoiceable_default = true;
        if ($project->create())
        {
            $project->add_members('resources', array($salesproject->owner));
            $project->add_members('contacts', array_keys($salesproject->contacts));

            org_openpsa_relatedto_plugin::create($project, 'org.openpsa.projects', $salesproject, 'org.openpsa.sales');
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'), sprintf($_MIDCOM->i18n->get_string('created project "%s"', 'org.openpsa.sales'), $project->title), 'ok');
            return $project;
        }
        return false;
    }

    /**
     * @todo Check if we already have an open task for this delivery?
     */
    function create_task($start, $end, $title , $source_task = null)
    {
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);
        $product = org_openpsa_products_product_dba::get_cached($this->_deliverable->product);

        // Check if we already have a project for the sales project
        $project = $this->_probe_project();

        // Create the task
        $task = new org_openpsa_projects_task_dba();
        $task->agreement = $this->_deliverable->id;
        $task->customer = $salesproject->customer;
        $task->title = $title;
        $task->description = $this->_deliverable->description;
        $task->start = $start;
        $task->end = $end;
        $task->plannedHours = $this->_deliverable->plannedUnits;

        $task->manager = $salesproject->owner;
        if ($project)
        {
            $task->up = $project->id;
            $task->orgOpenpsaAccesstype = $project->orgOpenpsaAccesstype;
            $task->orgOpenpsaOwnerWg = $project->orgOpenpsaOwnerWg;
        }

        if (!empty($source_task))
        {
            $task->priority = $source_task->priority;
        }

        // TODO: Figure out if we really want to keep this
        $task->hoursInvoiceableDefault = true;
        if ($task->create())
        {
            $task->add_members('contacts', array_keys($salesproject->contacts));
            org_openpsa_relatedto_plugin::create($task, 'org.openpsa.projects', $product, 'org.openpsa.products');

            // Copy tags from deliverable so we can seek resources
            $tagger = new net_nemein_tag_handler();
            $tagger->copy_tags($this->_deliverable, $task);

            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'), sprintf($_MIDCOM->i18n->get_string('created task "%s"', 'org.openpsa.sales'), $task->title), 'ok');
            return $task;
        }
        else
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                                     "The task for this cycle could not be created. Last Midgard error was: " . midcom_application::get_error_string());
            // This will exit.
        }
    }

    function calculate_cycles($months = null)
    {
        $cycle_time = $this->_deliverable->start;
        $end_time = $this->_deliverable->end;

        if (!is_null($months))
        {
            // We calculate how many cycles fit into the number of months, figure out the end of time
            $end_time = mktime(date('H', $cycle_time), date('m', $cycle_time), date('i', $cycle_time), date('m', $cycle_time) + $months, date('d', $cycle_time), date('Y', $cycle_time));
        }

        // Calculate from beginning to the end
        $cycles = 0;
        while (   $cycle_time < $end_time
               && $cycle_time != false)
        {
            $cycle_time = $this->calculate_cycle_next($cycle_time);

            if ($cycle_time <= $end_time)
            {
                $cycles++;
            }
        }
        return $cycles;
    }

    function calculate_cycle_next($time)
    {
        switch ($this->_deliverable->unit)
        {
            case 'd':
                // Daily recurring subscription
                require_once 'Calendar/Day.php';
                $this_day = new Calendar_Day(date('Y', $time), date('m', $time), date('d', $time));
                $next_cycle = $this_day->nextDay('object');
                break;
            case 'm':
                // Monthly recurring subscription
                require_once 'Calendar/Month.php';
                $this_month = new Calendar_Month(date('Y', $time), date('m', $time));
                $next_cycle = $this_month->nextMonth('object');
                $next_cycle->day = date('d', $time);
                break;
            case 'q':
                // Quarterly recurring subscription
                require_once 'Calendar/Month.php';
                $year = date('Y', $time);
                $month = date('m', $time);
                if ($month > 9)
                {
                    $month -= 12 ; // remove one year
                    $year++;
                }
                $month += 3; //add the quarter
                $next_cycle = new Calendar_Month($year, $month);
                $next_cycle->day = date('d' , $time);
                break;
            case 'y':
                // Yearly recurring subscription
                require_once 'Calendar/Year.php';
                $this_year = new Calendar_Year(date('Y', $time));
                $next_cycle = $this_year->nextYear('object');
                $next_cycle->month = date('m', $time);
                $next_cycle->day = date('d' , $time);
                break;
            default:
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('Unrecognized unit value "' . $this->_deliverable->unit . '" for deliverable ' . $this->_deliverable->guid . ", returning false" , MIDCOM_LOG_WARN);
                debug_pop();
                return false;
        }
        return $next_cycle->getTimestamp();
    }

}

?>