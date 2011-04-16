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

    public function __construct($deliverable)
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
        if (time() < $this->_deliverable->start)
        {
            debug_add('Subscription hasn\'t started yet, register the start-up event to $start');
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

        if ($this->_deliverable->state < ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED)
        {
            $this->_deliverable->state = ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED;
            $this->_deliverable->update();
        }

        if ($send_invoice)
        {
            $calculator = new org_openpsa_invoices_calculator();
            $this_cycle_amount = $calculator->process_deliverable($this->_deliverable, $cycle_number);
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
            $this->_notify_owner($cycle_number, null, $this_cycle_amount, $tasks_completed, $tasks_not_completed);
            return true;
        }

        if ($this->_create_at_entry($cycle_number + 1, $next_cycle_start))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    private function _create_at_entry($cycle_number, $start)
    {
        $_MIDCOM->load_library('midcom.services.at');

        $args = array
        (
            'deliverable' => $this->_deliverable->guid,
            'cycle'       => $cycle_number,
        );
        $at_entry = new midcom_services_at_entry_dba();
        $at_entry->start = $start;
        $at_entry->component = 'org.openpsa.sales';
        $at_entry->method = 'new_subscription_cycle';
        $at_entry->arguments = $args;

        if ($at_entry->create())
        {
            debug_add('AT entry for cycle ' . $cycle_number . ' created');
            org_openpsa_relatedto_plugin::create($at_entry, 'midcom.services.at', $this->_deliverable, 'org.openpsa.sales');
            return true;
        }
        else
        {
            debug_add('AT registration failed, last midgard error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            return false;
        }
    }

    private function _notify_owner($cycle_number, $next_run, $invoiced_sum, $tasks_completed, $tasks_not_completed, $new_task = null)
    {
        // Prepare notification to sales project owner
        $message = array();
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);
        $owner = midcom_db_person::get_cached($salesproject->owner);
        $customer = $salesproject->get_customer();

        if (is_null($next_run))
        {
            $next_run_label = $_MIDCOM->i18n->get_string('no more cycles', 'org.openpsa.sales');
        }
        else
        {
            $next_run_label = strftime('%x %X', $next_run);
        }

        // Title for long notifications
        $message['title'] = sprintf($_MIDCOM->i18n->get_string('subscription cycle %d closed for agreement %s (%s)', 'org.openpsa.sales'), $cycle_number, $this->_deliverable->title, $customer->get_label());

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

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $url = $siteconfig->get_node_full_url('org.openpsa.sales');
        $message['content'] .= $url . 'salesproject/' . $salesproject->guid . '/';

        // Content for short notifications
        $message['abstract'] = sprintf($_MIDCOM->i18n->get_string('%s: closed subscription cycle %d for agreement %s. invoiced %d. next cycle %s', 'org.openpsa.sales'), $customer->get_label(), $cycle_number, $this->_deliverable->title, $invoiced_sum, $next_run_label);

        // Send the message out
        $_MIDCOM->load_library('org.openpsa.notifications');
        org_openpsa_notifications::notify('org.openpsa.sales:new_subscription_cycle', $owner->guid, $message);
    }

    /**
     * @todo Check if we already have an open task for this delivery?
     */
    function create_task($start, $end, $title, $source_task = null)
    {
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);
        $product = org_openpsa_products_product_dba::get_cached($this->_deliverable->product);

        // Check if we already have a project for the sales project
        $project = $salesproject->get_project();

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
            $task->project = $project->id;
            $task->orgOpenpsaAccesstype = $project->orgOpenpsaAccesstype;
            $task->orgOpenpsaOwnerWg = $project->orgOpenpsaOwnerWg;
        }

        if (!empty($source_task))
        {
            $task->priority = $source_task->priority;
            $task->manager = $source_task->manager;
        }

        // TODO: Figure out if we really want to keep this
        $task->hoursInvoiceableDefault = true;
        if ($task->create())
        {
            $task->add_members('contacts', array_keys($salesproject->contacts));
            if (!empty($source_task))
            {
                $source_task->get_members();
                $task->add_members('resources', array_keys($source_task->resources));
            }
            org_openpsa_relatedto_plugin::create($task, 'org.openpsa.projects', $product, 'org.openpsa.products');

            // Copy tags from deliverable so we can seek resources
            $tagger = new net_nemein_tag_handler();
            $tagger->copy_tags($this->_deliverable, $task);

            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'), sprintf($_MIDCOM->i18n->get_string('created task "%s"', 'org.openpsa.sales'), $task->title), 'ok');
            return $task;
        }
        else
        {
            throw new midcom_error("The task for this cycle could not be created. Last Midgard error was: " . midcom_connection::get_error_string());
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
        $offset = '';
        switch ($this->_deliverable->unit)
        {
            case 'd':
                // Daily recurring subscription
                $offset = '+1 day';
                break;
            case 'm':
                // Monthly recurring subscription
                $offset = '+1 month';
                break;
            case 'q':
                // Quarterly recurring subscription
                $offset = '+3 months';
                break;
            case 'y':
                // Yearly recurring subscription
                $offset = '+1 year';
                break;
            default:
                debug_add('Unrecognized unit value "' . $this->_deliverable->unit . '" for deliverable ' . $this->_deliverable->guid . ", returning false", MIDCOM_LOG_WARN);
                return false;
        }
        $date = new DateTime($offset . ' ' . gmdate('Y-m-d', $time), new DateTimeZone('GMT'));
        $next_cycle = (int) $date->format('U');

        return $next_cycle;
    }
}
?>