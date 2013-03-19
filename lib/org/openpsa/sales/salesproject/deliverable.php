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

    const STATUS_NEW = 100;
    const STATUS_PROPOSED = 200;
    const STATUS_DECLINED = 300;
    const STATUS_ORDERED = 400;
    const STATUS_STARTED = 450;
    const STATUS_DELIVERED = 500;
    const STATUS_INVOICED = 600;

    /**
     * Combination property containing HTML depiction of the deliverable
     *
     * @var string
     */
    private $_deliverable_html = null;

    /**
     * Flag that controls if parent' price and cost need re-caculated if the current
     * object is saved.
     *
     * @var boolean
     */
    private $_update_parent_on_save = false;

    public function _on_creating()
    {
        $this->calculate_price(false);
        return true;
    }

    public function _on_created()
    {
        $this->_update_parent();
    }

    public function _on_updating()
    {
        $this->calculate_price(false);

        if (   $this->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
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

    public function _on_updated()
    {
        if ($this->_update_parent_on_save)
        {
            $this->_update_parent();
        }
    }

    public function _on_deleted()
    {
        $this->_update_parent();
    }

    public function get_parent()
    {
        try
        {
            $project = new org_openpsa_sales_salesproject_dba($this->salesproject);
            return $project;
        }
        catch (midcom_error $e)
        {
            $e->log();
            return null;
        }
    }

    private function _update_parent()
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

    function get_status()
    {
        switch ($this->state)
        {
            case org_openpsa_sales_salesproject_deliverable_dba::STATUS_NEW:
            case org_openpsa_sales_salesproject_deliverable_dba::STATUS_PROPOSED:
                return 'proposed';
            case org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED:
                return 'declined';
            case org_openpsa_sales_salesproject_deliverable_dba::STATUS_ORDERED:
                return 'ordered';
            case org_openpsa_sales_salesproject_deliverable_dba::STATUS_STARTED:
                return 'started';
            case org_openpsa_sales_salesproject_deliverable_dba::STATUS_DELIVERED:
                return 'delivered';
            case org_openpsa_sales_salesproject_deliverable_dba::STATUS_INVOICED:
                return 'invoiced';
        }
        return '';
    }

    function get_at_entries()
    {
        $mc = new org_openpsa_relatedto_collector($this->guid, 'midcom_services_at_entry_dba');
        $mc->add_object_constraint('method', '=', 'new_subscription_cycle');
        $at_entries = $mc->get_related_objects();

        return $at_entries;
    }

    /**
     *
     * helper function to copy some defaults from the given product to the deliverable
     * @param org_openpsa_products_product_dba $product
     */
    function copyFromProduct($product)
    {
        $this->product = $product->id;
        $this->title = $product->title;

        $this->unit = $product->unit;
        $this->costPerUnit = $product->cost;
        $this->costType = $product->costType;
        $this->pricePerUnit = $product->price;

        $this->orgOpenpsaObtype = $product->delivery;
        $this->description = $product->description;
        $this->supplier = $product->supplier;
    }

    function calculate_price($update = true)
    {
        $calculator_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('calculator');
        $calculator = new $calculator_class();
        $calculator->run($this);
        $cost = $calculator->get_cost();
        $price = $calculator->get_price();
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
            else
            {
                $this->_update_parent_on_save = true;
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
    function update_units($task_id = 0, $hours = null)
    {
        debug_add('Units before update: ' . $this->units . ", uninvoiceable: " . $this->uninvoiceableUnits);

        if (null === $hours)
        {
            $hours = array
            (
                'reported' => 0,
                'invoiced' => 0,
                'invoiceable' => 0
            );
        }
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

    function invoice()
    {
        if (   $this->state > org_openpsa_sales_salesproject_deliverable_dba::STATUS_INVOICED
            || $this->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION)
        {
            return false;
        }

        $calculator = new org_openpsa_invoices_calculator();
        $amount = $calculator->process_deliverable($this);

        if ($amount > 0)
        {
            // Update sales project and mark as delivered (if no other deliverables are active)
            $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
            $salesproject->mark_invoiced();
        }
        return true;
    }

    function decline()
    {
        if ($this->state >= org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED)
        {
            return false;
        }

        $this->state = org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED;

        if ($this->update())
        {
            // Update sales project if it doesn't have any open deliverables
            $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
            $qb->add_constraint('salesproject', '=', $this->salesproject);
            $qb->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED);
            if ($qb->count() == 0)
            {
                // No proposals that are not declined
                $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
                $salesproject->status = org_openpsa_sales_salesproject_dba::STATUS_LOST;
                $salesproject->update();
            }

            return true;
        }
        return false;
    }

    function order()
    {
        if ($this->state >= org_openpsa_sales_salesproject_deliverable_dba::STATUS_ORDERED)
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

        if ($product->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION)
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
                case org_openpsa_products_product_dba::TYPE_SERVICE:
                    $scheduler->create_task($this->start, $this->end, $this->title);
                    break;
                case org_openpsa_products_product_dba::TYPE_GOODS:
                    // TODO: Warehouse management: create new order
                default:
                    break;
            }
        }

        $this->state = org_openpsa_sales_salesproject_deliverable_dba::STATUS_ORDERED;

        if ($this->update())
        {
            // Update sales project and mark as won
            $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
            if ($salesproject->status != org_openpsa_sales_salesproject_dba::STATUS_WON)
            {
                $salesproject->status = org_openpsa_sales_salesproject_dba::STATUS_WON;
                $salesproject->update();
            }

            return true;
        }

        return false;
    }

    function deliver($update_deliveries = true)
    {
        if ($this->state > org_openpsa_sales_salesproject_deliverable_dba::STATUS_DELIVERED)
        {
            return false;
        }

        $product = org_openpsa_products_product_dba::get_cached($this->product);
        if ($product->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION)
        {
            // Subscriptions are ongoing, not one delivery
            return false;
        }

        // Check if we need to create task or ship goods
        if ($update_deliveries)
        {
            switch ($product->orgOpenpsaObtype)
            {
                case org_openpsa_products_product_dba::TYPE_SERVICE:
                    // Change status of tasks connected to the deliverable
                    $task_qb = org_openpsa_projects_task_dba::new_query_builder();
                    $task_qb->add_constraint('agreement', '=', $this->id);
                    $task_qb->add_constraint('status', '<', org_openpsa_projects_task_status_dba::CLOSED);
                    $tasks = $task_qb->execute();
                    foreach ($tasks as $task)
                    {
                        org_openpsa_projects_workflow::close($task, sprintf(midcom::get('i18n')->get_string('completed from deliverable %s', 'org.openpsa.sales'), $this->title));
                    }
                    break;
                case org_openpsa_products_product_dba::TYPE_GOODS:
                    // TODO: Warehouse management: mark product as shipped
                default:
                    break;
            }
        }

        $this->state = org_openpsa_sales_salesproject_deliverable_dba::STATUS_DELIVERED;
        $this->end = time();
        if ($this->update())
        {
            // Update sales project and mark as delivered (if no other deliverables are active)
            $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
            $salesproject->mark_delivered();

            midcom::get('uimessages')->add(midcom::get('i18n')->get_string('org.openpsa.sales', 'org.openpsa.sales'), sprintf(midcom::get('i18n')->get_string('marked deliverable "%s" delivered', 'org.openpsa.sales'), $this->title), 'ok');
            return true;
        }
        return false;
    }
}
?>