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
 * @property integer $up
 * @property integer $product
 * @property integer $supplier
 * @property integer $salesproject
 * @property string $title
 * @property string $description
 * @property float $price
 * @property float $invoiced
 * @property float $units
 * @property float $plannedUnits
 * @property float $uninvoiceableUnits
 * @property string $unit
 * @property float $pricePerUnit
 * @property boolean $invoiceByActualUnits
 * @property boolean $continuous
 * @property float $cost Actual cost of the delivery
 * @property float $plannedCost Original planned cost
 * @property float $costPerUnit Cost per unit, used as basis of calculations for the fields above
 * @property string $costType
 * @property integer $start Start can have two different meanings:
        		- for single deliveries, it's the time when delivery can start
        		- for subscriptions it's the subscription start
 * @property integer $end End can have two different meanings:
        		- for single deliveries, it's the delivery deadline
        		- for subscriptions it's the subscription end
 * @property integer $notify
 * @property integer $state State of the proposal/order
 * @property integer $orgOpenpsaObtype Used to a) distinguish OpenPSA objects in QB b) store object "subtype" (project vs task etc)
 * @package org.openpsa.sales
 */
class org_openpsa_sales_salesproject_deliverable_dba extends midcom_core_dbaobject
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_salesproject_deliverable';

    const STATE_NEW = 100;
    const STATE_DECLINED = 300;
    const STATE_ORDERED = 400;
    const STATE_STARTED = 450;
    const STATE_DELIVERED = 500;
    const STATE_INVOICED = 600;

    /**
     * @var boolean
     */
    private $_update_parent_on_save = false;

    public function _on_creating() : bool
    {
        $this->calculate_price(false);
        return true;
    }

    public function _on_created()
    {
        $this->_update_parent();
    }

    public function _on_updating() : bool
    {
        $this->calculate_price(false);

        if (   $this->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
            && $this->continuous) {
            $this->end = 0;
        } elseif ($this->end < $this->start) {
            $this->end = $this->start + 1;
        }
        return true;
    }

    public function _on_updated()
    {
        if ($this->_update_parent_on_save) {
            $this->_update_parent();
        }
    }

    public function _on_deleted()
    {
        $entries = $this->get_at_entries();
        foreach ($entries as $entry) {
            $entry->delete();
        }
        $this->_update_parent();
    }

    private function _update_parent()
    {
        $project = new org_openpsa_sales_salesproject_dba($this->salesproject);
        $project->calculate_price();
    }

    public function get_state() : string
    {
        switch ($this->state) {
            case self::STATE_NEW:
                return 'proposed';
            case self::STATE_DECLINED:
                return 'declined';
            case self::STATE_ORDERED:
                return 'ordered';
            case self::STATE_STARTED:
                return 'started';
            case self::STATE_DELIVERED:
                return 'delivered';
            case self::STATE_INVOICED:
                return 'invoiced';
        }
        return '';
    }

    /**
     * @return midcom_services_at_entry_dba[]
     */
    public function get_at_entries() : array
    {
        $mc = new org_openpsa_relatedto_collector($this->guid, midcom_services_at_entry_dba::class);
        $mc->add_object_constraint('method', '=', 'new_subscription_cycle');
        return $mc->get_related_objects();
    }

    public function calculate_price(bool $update = true)
    {
        $calculator_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('calculator');
        $calculator = new $calculator_class();
        $calculator->run($this);
        $cost = $calculator->get_cost();
        $price = $calculator->get_price();
        if (   $price != $this->price
            || $cost != $this->cost) {
            $this->price = $price;
            $this->cost = $cost;
            $this->_update_parent_on_save = true;
            if ($update) {
                $this->update();
                $this->_update_parent_on_save = false;
            }
        }
    }

    /**
     * Recalculate the deliverable's unit trackers based on data form a (recently updated) task
     */
    public function update_units()
    {
        debug_add('Units before update: ' . $this->units . ", uninvoiceable: " . $this->uninvoiceableUnits);

        $hours = [
            'reported' => 0,
            'invoiced' => 0,
            'invoiceable' => 0
        ];

        // List hours from tasks of the agreement
        $mc = org_openpsa_projects_task_dba::new_collector('agreement', $this->id);
        $other_tasks = $mc->get_rows(['reportedHours', 'invoicedHours', 'invoiceableHours']);

        foreach ($other_tasks as $other_task) {
            // Add the hours of the other tasks to agreement's totals
            $hours['reported'] += $other_task['reportedHours'];
            $hours['invoiced'] += $other_task['invoicedHours'];
            $hours['invoiceable'] += $other_task['invoiceableHours'];
        }

        // Update units on the agreement with invoiceable hours
        $units = $hours['invoiceable'];
        $uninvoiceableUnits = $hours['reported'] - ($hours['invoiceable'] + $hours['invoiced']);

        if (   $units != $this->units
            || $uninvoiceableUnits != $this->uninvoiceableUnits) {
            debug_add("agreement values have changed, setting units to " . $units . ", uninvoiceable: " . $uninvoiceableUnits);
            $this->units = $units;
            $this->uninvoiceableUnits = $uninvoiceableUnits;
            $this->_use_rcs = false;

            if (!$this->update()) {
                debug_add("Agreement #{$this->id} couldn't be saved to disk, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        } else {
            debug_add("Agreement values are unchanged, no update necessary");
        }
    }

    /**
     * Manually trigger a subscription cycle run.
     */
    public function run_cycle() : bool
    {
        $at_entries = $this->get_at_entries();
        if (!isset($at_entries[0])) {
            debug_add('No AT entry found');
            return false;
        }

        $entry = $at_entries[0];
        $scheduler = new org_openpsa_invoices_scheduler($this);

        if (!$scheduler->run_cycle($entry->arguments['cycle'])) {
            debug_add('Failed to run cycle');
            return false;
        }
        if (!$entry->delete()) {
            debug_add('Could not delete AT entry: ' . midcom_connection::get_error_string());
            return false;
        }
        return true;
    }

    public function get_cycle_identifier(int $time) : string
    {
        $date = new DateTime(gmdate('Y-m-d', $time), new DateTimeZone('GMT'));

        switch ($this->unit) {
            case 'm':
                // Monthly recurring subscription
                $identifier = $date->format('Y-m');
                break;
            case 'q':
                // Quarterly recurring subscription
                $identifier = ceil(((int)$date->format('n')) / 4) . 'Q' . $date->format('y');
                break;
            case 'hy':
                // Half-yearly recurring subscription
                $identifier = ceil(((int)$date->format('n')) / 6) . '/' . $date->format('Y');
                break;
            case 'y':
                // Yearly recurring subscription
                $identifier = $date->format('Y');
                break;
            default:
                debug_add('Unrecognized unit value "' . $this->unit . '" for deliverable ' . $this->guid, MIDCOM_LOG_INFO);
                $identifier = '';
        }

        return trim($this->title . ' ' . $identifier);
    }

    public function end_subscription() : bool
    {
        $this->state = self::STATE_INVOICED;
        if (!$this->update()) {
            return false;
        }
        $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
        $salesproject->mark_invoiced();

        return true;
    }

    public function invoice() : bool
    {
        if (   $this->state >= self::STATE_INVOICED
            || $this->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
            return false;
        }

        $calculator = new org_openpsa_invoices_calculator();
        $amount = $calculator->process_deliverable($this);

        if ($amount > 0) {
            $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
            $salesproject->mark_invoiced();
        }
        return true;
    }

    public function decline() : bool
    {
        if ($this->state >= self::STATE_DECLINED) {
            return false;
        }

        $this->state = self::STATE_DECLINED;

        if ($this->update()) {
            // Update sales project if it doesn't have any open deliverables
            $qb = self::new_query_builder();
            $qb->add_constraint('salesproject', '=', $this->salesproject);
            $qb->add_constraint('state', '<>', self::STATE_DECLINED);
            if ($qb->count() == 0) {
                // No proposals that are not declined
                $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
                $salesproject->state = org_openpsa_sales_salesproject_dba::STATE_LOST;
                $salesproject->update();
            }

            return true;
        }
        return false;
    }

    public function order() : bool
    {
        if ($this->state >= self::STATE_ORDERED) {
            return false;
        }

        if ($this->invoiceByActualUnits) {
            $this->cost = 0;
            $this->units = 0;
        }

        // Check what kind of order this is
        $product = org_openpsa_products_product_dba::get_cached($this->product);
        $scheduler = new org_openpsa_invoices_scheduler($this);

        if ($product->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
            // This is a new subscription, initiate the cycle but don't send invoice
            if (!$scheduler->run_cycle(1, false)) {
                return false;
            }
        } elseif ($product->orgOpenpsaObtype === org_openpsa_products_product_dba::TYPE_SERVICE) {
            $scheduler->create_task($this->start, $this->end, $this->title);
        }
        // TODO: Warehouse management: create new order (for org_openpsa_products_product_dba::TYPE_GOODS)

        $this->state = self::STATE_ORDERED;

        if ($this->update()) {
            // Update sales project and mark as won
            $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
            if ($salesproject->state != org_openpsa_sales_salesproject_dba::STATE_WON) {
                $salesproject->state = org_openpsa_sales_salesproject_dba::STATE_WON;
                $salesproject->update();
            }

            return true;
        }

        return false;
    }

    public function deliver(bool $update_deliveries = true) : bool
    {
        if ($this->state > self::STATE_DELIVERED) {
            return false;
        }

        $product = org_openpsa_products_product_dba::get_cached($this->product);
        if ($product->delivery == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
            // Subscriptions are ongoing, not one delivery
            return false;
        }

        $this->state = self::STATE_DELIVERED;
        $this->end = time();
        if ($this->update()) {
            // Update sales project and mark as delivered (if no other deliverables are active)
            $salesproject = new org_openpsa_sales_salesproject_dba($this->salesproject);
            $salesproject->mark_delivered();

            midcom::get()->uimessages->add(midcom::get()->i18n->get_string('org.openpsa.sales', 'org.openpsa.sales'), sprintf(midcom::get()->i18n->get_string('marked deliverable "%s" delivered', 'org.openpsa.sales'), $this->title));

            // Check if we need to create task or ship goods
            if (   $update_deliveries
                && $product->orgOpenpsaObtype === org_openpsa_products_product_dba::TYPE_SERVICE) {
                // Change status of tasks connected to the deliverable
                $qb = org_openpsa_projects_task_dba::new_query_builder();
                $qb->add_constraint('agreement', '=', $this->id);
                $qb->add_constraint('status', '<', org_openpsa_projects_task_status_dba::CLOSED);
                foreach ($qb->execute() as $task) {
                    org_openpsa_projects_workflow::close($task, sprintf(midcom::get()->i18n->get_string('completed from deliverable %s', 'org.openpsa.sales'), $this->title));
                }
                // TODO: Warehouse management: mark product as shipped (for org_openpsa_products_product_dba::TYPE_GOODS)
            }

            return true;
        }
        return false;
    }
}
