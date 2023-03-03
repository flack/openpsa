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
class org_openpsa_invoices_scheduler
{
    use midcom_baseclasses_components_base;

    private org_openpsa_sales_salesproject_deliverable_dba $_deliverable;

    /**
     * The day of month on which subscriptions are invoiced (if none is set, they are invoiced continuously)
     *
     * @var int
     */
    private $subscription_day;

    public function __construct(org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        $this->_component = 'org.openpsa.invoices';
        $this->_deliverable = $deliverable;
        $this->subscription_day = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('subscription_invoice_day_of_month');
    }

    /**
     * Initiates a new subscription cycle and registers a midcom.services.at call for the next cycle.
     *
     * The subscription cycles rely on midcom.services.at. I'm not sure if it is wise to rely on it for such
     * a totally mission critical part of OpenPSA. Some safeguards might be wise to add.
     */
    public function run_cycle($cycle_number, bool $send_invoice = true) : bool
    {
        if (time() < $this->_deliverable->start) {
            debug_add('Subscription hasn\'t started yet, register the start-up event to $start');
            return $this->_create_at_entry($cycle_number, $this->_deliverable->start);
        }

        debug_add('Running cycle ' . $cycle_number . ' for deliverable "' . $this->_deliverable->title . '"');

        $this_cycle_start = $this->get_cycle_start($cycle_number, time());
        if ($this->subscription_day && $cycle_number == 1) {
            // If there's a fixed day for invoicing, get_cycle_start already picked a future date for cycle 1
            $next_cycle_start = $this_cycle_start + 2; // +2 so we don't get overlaps in task
        } else {
            $next_cycle_start = $this->calculate_cycle_next($this_cycle_start);
        }
        $product = org_openpsa_products_product_dba::get_cached($this->_deliverable->product);

        if ($this->_deliverable->state < org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED) {
            $this->_deliverable->state = org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED;
            $this->_deliverable->update();
        }

        if ($send_invoice) {
            $calculator = new org_openpsa_invoices_calculator();
            $this_cycle_amount = $calculator->process_deliverable($this->_deliverable, $cycle_number);
        }

        $tasks_completed = [];
        $tasks_not_completed = [];
        $new_task = null;

        if ($product->orgOpenpsaObtype == org_openpsa_products_product_dba::TYPE_SERVICE) {
            // Close previous task(s)
            $last_task = null;

            $qb = org_openpsa_projects_task_dba::new_query_builder();
            $qb->add_constraint('agreement', '=', $this->_deliverable->id);
            $qb->add_constraint('status', '<', org_openpsa_projects_task_status_dba::COMPLETED);

            foreach ($qb->execute() as $task) {
                if (org_openpsa_projects_workflow::complete($task, sprintf($this->_i18n->get_string('completed by subscription %s', 'org.openpsa.sales'), $cycle_number))) {
                    $tasks_completed[] = $task;
                } else {
                    $tasks_not_completed[] = $task;
                }
                $last_task = $task;
            }

            // Create task for the duration of this cycle
            $task_title = $this->_deliverable->get_cycle_identifier($this_cycle_start);
            $new_task = $this->create_task($this_cycle_start, $next_cycle_start - 1, $task_title, $last_task);
        }

        // TODO: Warehouse management: create new order
        if (   $this->_deliverable->end < $next_cycle_start
            && $this->_deliverable->end != 0) {
            debug_add('Do not register next cycle, the contract ends before');
            return $this->_deliverable->end_subscription();
        }

        if (!$this->_create_at_entry($cycle_number + 1, $next_cycle_start)) {
            return false;
        }
        if ($send_invoice) {
            $data = [
                'cycle_number' => $cycle_number,
                'next_run' => $next_cycle_start,
                'invoiced_sum' => $this_cycle_amount,
                'tasks_completed' => $tasks_completed,
                'tasks_not_completed' => $tasks_not_completed
            ];
            $this->_notify_owner($calculator, $new_task, $data);
        }
        return true;
    }

    private function _create_at_entry($cycle_number, int $start) : bool
    {
        $args = [
            'deliverable' => $this->_deliverable->guid,
            'cycle'       => $cycle_number,
        ];
        $at_entry = new midcom_services_at_entry_dba();
        $at_entry->start = $start;
        $at_entry->component = 'org.openpsa.sales';
        $at_entry->method = 'new_subscription_cycle';
        $at_entry->arguments = $args;

        if (!$at_entry->create()) {
            debug_add('AT registration failed, last midgard error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            return false;
        }
        debug_add('AT entry for cycle ' . $cycle_number . ' created');
        org_openpsa_relatedto_plugin::create($at_entry, 'midcom.services.at', $this->_deliverable, 'org.openpsa.sales');
        return true;
    }

    private function _notify_owner(org_openpsa_invoices_calculator $calculator, ?org_openpsa_projects_task_dba $new_task, array $data)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $message = [];
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);
        try {
            $owner = midcom_db_person::get_cached($salesproject->owner);
        } catch (midcom_error $e) {
            $e->log();
            return;
        }
        $customer = $salesproject->get_customer();
        $l10n = $this->_i18n->get_l10n('org.openpsa.sales');
        if ($data['next_run'] === null) {
            $next_run_label = $l10n->get('no more cycles');
        } else {
            $next_run_label = $l10n->get_formatter()->date($data['next_run']);
        }

        // Title for long notifications
        $message['title'] = sprintf($l10n->get('subscription cycle %d closed for agreement %s (%s)'), $data['cycle_number'], $this->_deliverable->title, $customer->get_label());

        // Content for long notifications
        $message['content'] = "{$message['title']}\n\n";
        $message['content'] .= $l10n->get('invoiced') . ': ' . $l10n->get_formatter()->number($data['invoiced_sum']) . "\n\n";

        if ($data['invoiced_sum'] > 0) {
            $invoice = $calculator->get_invoice();
            $message['content'] .= $this->_l10n->get('invoice') . " {$invoice->number}:\n";
            $url = $siteconfig->get_node_full_url('org.openpsa.invoices');
            $message['content'] .= $url . 'invoice/' . $invoice->guid . "/\n\n";
        }

        $message['content'] .= $this->render_task_info($l10n->get('tasks completed'), $data['tasks_completed']);
        $message['content'] .= $this->render_task_info($l10n->get('tasks not completed'), $data['tasks_not_completed']);

        if ($new_task) {
            $message['content'] .= "\n" . $l10n->get('created new task') . ":\n";
            $message['content'] .= "{$new_task->title}\n";
        }

        $message['content'] .= "\n" . $l10n->get('next run') . ": {$next_run_label}\n\n";
        $message['content'] .= $this->_i18n->get_string('agreement', 'org.openpsa.projects') . ":\n";

        $url = $siteconfig->get_node_full_url('org.openpsa.sales');
        $message['content'] .= $url . 'deliverable/' . $this->_deliverable->guid . '/';

        // Content for short notifications
        $message['abstract'] = sprintf(
            $l10n->get('%s: closed subscription cycle %d for agreement %s. invoiced %d. next cycle %s'),
            $customer->get_label(),
            $data['cycle_number'],
            $this->_deliverable->title,
            $data['invoiced_sum'],
            $next_run_label);

        // Send the message out
        org_openpsa_notifications::notify('org.openpsa.sales:new_subscription_cycle', $owner->guid, $message);
    }

    private function render_task_info(string $label, array $tasks) : string
    {
        $content = '';
        if (!empty($tasks)) {
            $content .= "\n{$label}:\n";

            foreach ($tasks as $task) {
                $content .= "{$task->title}: {$task->reportedHours}h\n";
            }
        }
        return $content;
    }

    /**
     * @todo Check if we already have an open task for this delivery?
     */
    public function create_task(int $start, int $end, string $title, org_openpsa_projects_task_dba $source_task = null) : org_openpsa_projects_task_dba
    {
        // Create the task
        $task = new org_openpsa_projects_task_dba();
        $task->title = $title;
        $task->start = $start;
        $task->end = $end;
        // TODO: Figure out if we really want to keep this
        $task->hoursInvoiceableDefault = true;

        $task->agreement = $this->_deliverable->id;
        $task->description = $this->_deliverable->description;
        $task->plannedHours = $this->_deliverable->plannedUnits;

        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);
        $task->customer = $salesproject->customer;
        $task->manager = $salesproject->owner;

        $project = $salesproject->get_project();
        $task->project = $project->id;
        $task->orgOpenpsaAccesstype = $project->orgOpenpsaAccesstype;
        $task->orgOpenpsaOwnerWg = $project->orgOpenpsaOwnerWg;

        if (!empty($source_task)) {
            $task->priority = $source_task->priority;
            $task->manager = $source_task->manager;
            $task->hoursInvoiceableDefault = $source_task->hoursInvoiceableDefault;
        }

        if (!$task->create()) {
            throw new midcom_error("The task for this cycle could not be created. Last Midgard error was: " . midcom_connection::get_error_string());
        }
        $task->add_members('contacts', array_keys($salesproject->contacts));
        if (!empty($source_task)) {
            $source_task->get_members();
            $task->add_members('resources', array_keys($source_task->resources));
        }

        // Copy tags from deliverable so we can seek resources
        $tagger = new net_nemein_tag_handler();
        $tagger->copy_tags($this->_deliverable, $task);

        midcom::get()->uimessages->add($this->_i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'), sprintf($this->_i18n->get_string('created task "%s"', 'org.openpsa.sales'), $task->title));
        return $task;
    }

    /**
     * Calculcate remaining cycles until salesproject's end or the specified number of months passes
     *
     * @param integer $months The maximum number of months to look forward
     * @param integer $start The timestamp from which to begin
     */
    public function calculate_cycles(int $months = null, int $start = null) : int
    {
        if ($start === null) {
            $start = time();
        }
        $cycles = 0;
        $cycle_time = $this->_deliverable->start;
        $end_time = $this->_deliverable->end;

        // This takes care of invalid/unsupported unit configs
        if ($this->calculate_cycle_next($cycle_time) === false) {
            return $cycles;
        }

        while ($cycle_time < $start) {
            $cycle_time = $this->calculate_cycle_next($cycle_time);
        }

        if ($months !== null) {
            $end_time = mktime(date('H', $cycle_time), date('m', $cycle_time), date('i', $cycle_time), date('m', $cycle_time) + $months, date('d', $cycle_time), date('Y', $cycle_time));
        }

        $cycles = 0;
        while ($cycle_time < $end_time) {
            $cycle_time = $this->calculate_cycle_next($cycle_time);
            if ($cycle_time <= $end_time) {
                $cycles++;
            }
        }
        return $cycles;
    }

    public function calculate_cycle_next(int $time)
    {
        $date = new DateTime('@' . $time);
        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $date->setTime(0, 0, 0);

        switch ($this->_deliverable->unit) {
            case 'm':
                // Monthly recurring subscription
                $new_date = $this->_add_month($date, 1);
                break;
            case 'q':
                // Quarterly recurring subscription
                $new_date = $this->_add_month($date, 3);
                break;
            case 'hy':
                // Half-yearly recurring subscription
                $new_date = $this->_add_month($date, 6);
                break;
            case 'y':
                // Yearly recurring subscription
                $new_date = clone $date;
                $new_date->modify('+1 year');
                break;
            default:
                debug_add('Unrecognized unit value "' . $this->_deliverable->unit . '" for deliverable ' . $this->_deliverable->guid . ", returning false", MIDCOM_LOG_WARN);
                return false;
        }

        //If previous cycle was run at the end of the month, the new one should be at the end of the month as well
        if (   $date->format('t') == $date->format('j')
            && $new_date->format('t') != $new_date->format('j')) {
            $new_date->setDate((int) $new_date->format('Y'), (int) $new_date->format('m'), (int) $new_date->format('t'));
        }
        return (int) $new_date->format('U');
    }

    /**
     * Workaround for odd PHP DateTime behavior where for example
     * 2012-10-31 + 1 month would return 2012-12-01. This function makes
     * sure the new date is always in the expected month (so in the example above
     * it would return 2012-11-30)
     *
     * @param Datetime $orig Original timestamp
     * @param integer $offset number of months to add
     */
    private function _add_month(Datetime $orig, int $offset) : DateTime
    {
        $new_date = clone $orig;
        $new_date->modify('+' . $offset . ' months');
        $control = clone $new_date;
        $control->modify('-' . $offset . ' months');

        while ($orig->format('m') !== $control->format('m')) {
            $new_date->modify('-1 day');
            $control = clone $new_date;
            $control->modify('-' . $offset . ' months');
        }

        return $new_date;
    }

    public function get_cycle_start($cycle_number, int $time)
    {
        if ($cycle_number == 1) {
            if ($this->subscription_day) {
                return gmmktime(0, 0, 0, date('n', $time) + 1, $this->subscription_day, date('Y', $time));
            }

            // no explicit day of month set for invoicing, use the deliverable start date
            return $this->_deliverable->start;
        }

        // cycle number > 1
        return $time;
    }
}
