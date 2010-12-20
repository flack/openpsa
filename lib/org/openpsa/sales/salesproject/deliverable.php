<?php
/**
 * @package org.openpsa.sales
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped class for access to deliverables
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_salesproject_deliverable_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_salesproject_deliverable';

    /**
     * Combination property containing HTML depiction of the deliverable
     *
     * @var string
     */
    private $_deliverable_html = null;

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    function get_parent_guid_uncached()
    {
        if ($this->up != 0)
        {
            $parent = new org_openpsa_sales_salesproject_deliverable_dba($this->up);
            return $parent->guid;
        }
        else if ($this->salesproject != 0)
        {
            $parent = new org_openpsa_sales_salesproject_dba($this->salesproject);
            return $parent->guid;
        }
        else
        {
            return null;
        }
    }

    public function _on_creating()
    {
        $this->calculate_price(false);
        return true;
    }

    public function _on_updating()
    {
        $this->calculate_price(false);

        if (   $this->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION
            && $this->continuous == true)
        {
            $this->end = 0;
        }
        else if ($this->end < $this->start)
        {
            $this->end = $this->start + 1;
        }
        return true;
    }

    public function _on_deleted()
    {
        $parent = $this->get_parent();
        if (is_object($parent))
        {
            $parent->calculate_price();
        }
    }

    public function __get($property)
    {
        if ($property == 'deliverable_html')
        {
            if (is_null($this->_deliverable_html))
            {
                $this->_generate_html();
            }
            return $this->_deliverable_html;
        }
        return parent::__get($property);
    }

    private function _generate_html()
    {
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->salesproject);

        $this->_deliverable_html  = "<span class=\"org_openpsa_sales_salesproject_deliverable\">\n";
        $this->_deliverable_html .= "    <span class=\"title\">{$this->title}</span>\n";
        $this->_deliverable_html .= "    (<span class=\"salesproject\">{$salesproject->title}</span>)\n";
        $this->_deliverable_html .= "</span>\n";
    }

    /**
     * List subcomponents of this deliverable
     *
     * @return Array
     */
    private function _get_components()
    {
        $deliverable_qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $deliverable_qb->add_constraint('salesproject', '=', $this->salesproject);
        $deliverable_qb->add_constraint('up', '=', $this->id);
        $deliverables = $deliverable_qb->execute();
        return $deliverables;
    }

    function get_status()
    {
        switch ($this->state)
        {
            case ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_NEW:
            case ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_PROPOSED:
                return 'proposed';
            case ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED:
                return 'declined';
            case ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_ORDERED:
                return 'ordered';
            case ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED:
                return 'started';
            case ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DELIVERED:
                return 'delivered';
            case ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_INVOICED:
                return 'invoiced';
        }
        return '';
    }

    function get_at_entries()
    {
        $_MIDCOM->load_library('midcom.services.at');

        $mc = new org_openpsa_relatedto_collector($this->guid, 'midcom_services_at_entry_dba');
        $at_entries = $mc->get_related_objects();

        return $at_entries;
    }

    function calculate_price($update = true)
    {
        if ($this->id)
        {
            // Check if we have subcomponents
            $deliverables = $this->_get_components();
            if (count($deliverables) > 0)
            {
                // If subcomponents exist, the price and cost per unit default to the
                // sum of price and cost of all subcomponents
                $pricePerUnit = 0;
                $costPerUnit = 0;

                foreach ($deliverables as $deliverable)
                {
                    $pricePerUnit = $pricePerUnit + $deliverable->price;
                    $costPerUnit = $costPerUnit + $deliverable->cost;
                }

                $this->pricePerUnit = $pricePerUnit;
                $this->costPerUnit = $costPerUnit;

                // We can't have percentage-based cost type if the agreement
                // has subcomponents
                $this->costType = 'm';
            }
        }

        if (   $this->invoiceByActualUnits
            || $this->plannedUnits == 0)
        {
            // In most cases we calculate the price based on the actual units entered
            $price = $this->units * $this->pricePerUnit;
        }
        else
        {
            // But in some deals we use the planned units instead
            $price = $this->plannedUnits * $this->pricePerUnit;
        }

        // Count cost based on the cost type
        switch ($this->costType)
        {
            case '%':
                // The cost is a percentage of the price
                $cost = $price / 100 * $this->costPerUnit;
                break;
            default:
            case 'm':
                // The cost is a fixed sum per unit
                $cost = $this->units * $this->costPerUnit;
                break;
        }
        if (   $price != $this->price
            || $cost != $this->cost)
        {
            $this->price = $price;
            $this->cost = $cost;

            if ($update)
            {
                $this->update();
                $parent = $this->get_parent();
                if (is_object($parent))
                {
                    $parent->calculate_price();
                }
            }
        }
    }

    /**
     * Helper function to recalculate the deliverable's unit trackers based on data form a
     * (recently updated) task
     *
     * @param integer $task_id The ID of the task that requested the update
     * @param array $hours The task's hours
     */
    function update_units($task_id, $hours)
    {
        debug_add('Units before update: ' . $this->units . ", uninvoiceable: " . $this->uninvoiceableUnits);

        $agreement_hours = $hours;

        // List hours from tasks of the agreement
        $mc = org_openpsa_projects_task_dba::new_collector('agreement', $this->id);
        $mc->add_value_property('reportedHours');
        $mc->add_value_property('invoicedHours');
        $mc->add_value_property('invoiceableHours');
        $mc->add_constraint('id', '<>', $task_id);
        $mc->execute();
        $other_tasks = $mc->list_keys();

        foreach ($other_tasks as $guid => $task)
        {
            // Add the hours of the other tasks to agreement's totals
            $agreement_hours['reported'] += $mc->get_subkey($guid, 'reportedHours');
            $agreement_hours['invoiced'] += $mc->get_subkey($guid, 'invoicedHours');
            $agreement_hours['invoiceable'] += $mc->get_subkey($guid, 'invoiceableHours');
        }

        // Update units on the agreement with invoiceable hours
        $units = $agreement_hours['invoiceable'];
        $uninvoiceableUnits = $agreement_hours['reported'] - ($agreement_hours['invoiceable'] + $agreement_hours['invoiced']);

        // Cast to string as workaround for #717
        if (   $units != $this->units
            || (string) $uninvoiceableUnits != (string) $this->uninvoiceableUnits)
        {
            debug_add("agreement values have changed, setting units to " . $units . ", uninvoiceable: " . $uninvoiceableUnits);
            $this->units = $units;
            $this->uninvoiceableUnits = $uninvoiceableUnits;
            $this->_use_rcs = false;
            $this->_use_activitystream = false;

            $stat = $this->update();

            if (!$stat)
            {
                debug_add("Agreement #{$this->id} couldn't be saved to disk, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }
        else
        {
            debug_add("Agreement values are unchanged, no update necessary");
        }
    }

    function invoice($sum, $generate_invoice = true)
    {
        if ($this->state > ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_INVOICED)
        {
            return false;
        }

        $product = org_openpsa_products_product_dba::get_cached($this->product);
        if ($product->delivery == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
        {
            // Subscriptions are invoiced by subscription::run_cycle method
            return false;
        }

        $open_amount = $this->price - $this->invoiced;

        /* if generate_invoice is set to false, we most likely generate one for
         *  multiple deliverables, so we skip the consistency check
         */
        if (   $generate_invoice
            && $sum > $open_amount)
        {
            $_MIDCOM->uimessages->add
            (
                $_MIDCOM->i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'),
                sprintf($_MIDCOM->i18n->get_string('the amount youre trying to invoice %s exceeds the open amount of the deliverable %s', 'org.openpsa.sales'), $sum, $open_amount),
                'error'
            );
            return false;
        }

        if (   $generate_invoice
            && $sum > 0)
        {
            // Generate org.openpsa.invoices invoice
            $this->_create_invoice($sum, "{$this->title}\n\n{$this->description}");
        }

        $this->state = ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_INVOICED;
        $this->invoiced = $this->invoiced + $sum;
        $this->update();

        // Update sales project and mark as delivered (if no other deliverables are active)
        $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
        $salesproject->mark_invoiced();

        return true;
    }

    /**
     * Send an invoice from the deliverable.
     *
     * Creates a new, unsent org.openpsa.invoices object
     * and adds a relation between it and the deliverable.
     *
     * @param float $sum The invoice sum
     * @param string $description The invoice description
     */
    private function _create_invoice($sum, $description)
    {
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->salesproject);

        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice->customer = $salesproject->customer;
        $invoice->number = org_openpsa_invoices_invoice_dba::generate_invoice_number();
        $invoice->owner = $salesproject->owner;

        $invoice->vat = $invoice->get_default_vat();
        $invoice->due = ($invoice->get_default_due() * 3600 * 24) + time();

        $invoice->description = $invoice->description . "\n\n" . $description;
        $invoice->sum = $sum;

        if (!$invoice->create())
        {
            throw new midcom_error("Invoice could not be created. Last Midgard error: " . midcom_connection::get_error_string());
        }

        // TODO: Create invoicing task if assignee is defined

        // Mark the tasks (and hour reports) related to this agreement as invoiced
        $task_qb = org_openpsa_projects_task_dba::new_query_builder();
        $task_qb->add_constraint('agreement', '=', $this->id);
        $tasks = $task_qb->execute();

        foreach ($tasks as $task)
        {
            org_openpsa_projects_workflow::mark_invoiced($task, $invoice);
        }
    }

    function decline()
    {
        if ($this->state >= ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED)
        {
            return false;
        }

        $this->state = ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED;

        if ($this->update())
        {
            // Mark subcomponents as declined also
            $deliverables = $this->_get_components();
            if (count($deliverables) > 0)
            {
                foreach ($deliverables as $deliverable)
                {
                    $deliverable->decline();
                }
            }

            // Update sales project if it doesn't have any open deliverables
            $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
            $qb->add_constraint('salesproject', '=', $this->salesproject);
            $qb->add_constraint('state', '<>', ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED);
            if ($qb->count() == 0)
            {
                // No proposals that are not declined
                $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
                $salesproject->status = ORG_OPENPSA_SALESPROJECTSTATUS_LOST;
                $salesproject->update();
            }

            return true;
        }
        return false;
    }

    function order()
    {
        if ($this->state >= ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_ORDERED)
        {
            return false;
        }

        // Cache the original cost values intended and reset the fields
        $this->plannedUnits = $this->units;
        $this->plannedCost = $this->cost;
        if ($this->invoiceByActualUnits)
        {
            $this->cost = 0;
            $this->units = 0;
        }

        // Check what kind of order this is
        $product = org_openpsa_products_product_dba::get_cached($this->product);
        $scheduler = new org_openpsa_invoices_scheduler($this);

        if ($product->delivery == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
        {
            // This is a new subscription, initiate the cycle but don't send invoice
            if (!$scheduler->run_cycle(1, false))
            {
                return false;
            }
        }
        else
        {
            // Check if we need to create task or ship goods
            switch ($product->orgOpenpsaObtype)
            {
                case ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE:
                    $scheduler->create_task($this->start, $this->end, $this->title);
                    break;
                case ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_GOODS:
                    // TODO: Warehouse management: create new order
                default:
                    break;
            }
        }

        $this->state = ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_ORDERED;

        if ($this->update())
        {
            // Mark subcomponents as ordered also
            $deliverables = $this->_get_components();
            if (count($deliverables) > 0)
            {
                foreach ($deliverables as $deliverable)
                {
                    $deliverable->order();
                }
            }

            // Update sales project and mark as won
            $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
            if ($salesproject->status != ORG_OPENPSA_SALESPROJECTSTATUS_WON)
            {
                $salesproject->status = ORG_OPENPSA_SALESPROJECTSTATUS_WON;
                $salesproject->update();
            }

            return true;
        }

        return false;
    }

    function deliver($update_deliveries = true)
    {
        if ($this->state > ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DELIVERED)
        {
            return false;
        }

        $product = org_openpsa_products_product_dba::get_cached($this->product);
        if ($product->delivery == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
        {
            // Subscriptions are ongoing, not one delivery
            return false;
        }

        // Check if we need to create task or ship goods
        if ($update_deliveries)
        {
            switch ($product->orgOpenpsaObtype)
            {
                case ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE:
                    // Change status of tasks connected to the deliverable
                    $task_qb = org_openpsa_projects_task_dba::new_query_builder();
                    $task_qb->add_constraint('agreement', '=', $this->id);
                    $task_qb->add_constraint('status', '<', ORG_OPENPSA_TASKSTATUS_CLOSED);
                    $tasks = $task_qb->execute();
                    foreach ($tasks as $task)
                    {
                        org_openpsa_projects_workflow::close($task, sprintf($_MIDCOM->i18n->get_string('completed from deliverable %s', 'org.openpsa.sales'), $this->title));
                    }
                    break;
                case ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_GOODS:
                    // TODO: Warehouse management: mark product as shipped
                default:
                    break;
            }
        }

        $this->state = ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DELIVERED;
        $this->end = time();
        if ($this->update())
        {
            // Mark subcomponents as delivered also
            $deliverables = $this->_get_components();
            if (count($deliverables) > 0)
            {
                foreach ($deliverables as $deliverable)
                {
                    $deliverable->deliver($update_deliveries);
                }
            }

            // Update sales project and mark as delivered (if no other deliverables are active)
            $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
            $salesproject->mark_delivered();

            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'), sprintf($_MIDCOM->i18n->get_string('marked deliverable "%s" delivered', 'org.openpsa.sales'), $this->title), 'ok');
            return true;
        }
        return false;
    }
}
?>