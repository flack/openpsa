<?php
/**
 * @package org.openpsa.sales
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped base class, keep logic here
 *
 * @property string $code
 * @property string $title
 * @property string $description
 * @property integer $start
 * @property integer $end
 * @property integer $status Current project status
 * @property integer $manager
 * @property integer $customer
 * @property integer $customerContact
 * @property float $plannedHours
 * @property float $reportedHours
 * @property float $invoicedHours
 * @property float $invoiceableHours
 * @property integer $orgOpenpsaAccesstype Shortcut for various ACL scenarios
 * @property string $orgOpenpsaOwnerWg The "owner" workgroup of this object
 * @property integer $state
 * @property integer $probability
 * @property float $value
 * @property float $profit
 * @property float $price
 * @property float $cost
 * @property integer $closeEst
 * @property integer $owner Alias for manager
 * @package org.openpsa.sales
 */
class org_openpsa_sales_salesproject_dba extends midcom_core_dbaobject implements org_openpsa_invoices_interfaces_customer
{
    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_salesproject';

    public array $autodelete_dependents = [
        org_openpsa_projects_role_dba::class => 'project'
    ];

    //org.openpsa.sales salesproject states
    const STATE_LOST = 11000;
    const STATE_ACTIVE = 11050;
    const STATE_WON = 11100;
    const STATE_DELIVERED = 11200;
    const STATE_INVOICED = 11300;

    // ... and because these constants suck for pratically everything..
    private array $states = [
        self::STATE_LOST => 'lost',
        self::STATE_ACTIVE => 'active',
        self::STATE_WON => 'won',
        self::STATE_DELIVERED => 'delivered',
        self::STATE_INVOICED => 'invoiced'
    ];

    //org.openpsa.sales role types
    const ROLE_MEMBER = 10500;

    /**
     * Shorthand access for contact members
     */
    private ?array $_contacts = null;

    public function refresh() : bool
    {
        $this->_contacts = null;
        return parent::refresh();
    }

    public function get_state() : string
    {
        if (array_key_exists($this->state, $this->states)) {
            return $this->states[$this->state];
        }
        return $this->states[self::STATE_ACTIVE];
    }

    /**
     * Calculates the prices of deliverables and adds them up to the salesproject value
     *
     * For subscriptions, we use already invoiced sums plus expected values (i.e. deliverable price)
     * until the deliverable's end time (or 12 months if the subscription is continuous)
     * Single deliveries are calculated based on their price and the already invoiced sum, when invoice by
     * actual units is true
     */
    public function calculate_price()
    {
        $value = 0;
        $cost = 0;

        $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $qb->add_constraint('salesproject', '=', $this->id);
        $qb->add_constraint('up', '=', 0);
        $qb->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
        foreach ($qb->execute() as $deliverable) {
            if ($deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
                $scheduler = new org_openpsa_invoices_scheduler($deliverable);
                if ($deliverable->end == 0) {
                    // FIXME: Get this from config key 'subscription_profit_months'
                    $cycles = $scheduler->calculate_cycles(12);
                } else {
                    $cycles = $scheduler->calculate_cycles();
                }
                $value += ($deliverable->price * $cycles) + $deliverable->invoiced;
                $cost += $deliverable->cost * $cycles;
            } else {
                $value += $deliverable->price;
                $cost += $deliverable->cost;
                if ($deliverable->invoiceByActualUnits) {
                    $value += $deliverable->invoiced;
                }
            }
        }
        $profit = $value - $cost;
        if (   $this->value != $value
            || $this->profit != $profit) {
            $this->value = $value;
            $this->profit = $value - $cost;
            $this->update();
        }
    }

    public static function generate_salesproject_number() : string
    {
        // TODO: Make configurable
        $year = date('Y');
        $qb = self::new_query_builder();
        $qb->add_constraint('metadata.created', '>=', $year . '-01-01 00:00:00');
        $previous = $qb->count_unchecked();

        return sprintf('%d-%04d', $year, $previous + 1);
    }

    public function get_project() : org_openpsa_projects_project
    {
        return new org_openpsa_projects_project($this->id);
    }

    public function get_customer()
    {
        if (!empty($this->customer)) {
            try {
                return org_openpsa_contacts_group_dba::get_cached($this->customer);
            } catch (midcom_error $e) {
                $e->log();
            }
        }
        if (!empty($this->customerContact)) {
            try {
                return org_openpsa_contacts_person_dba::get_cached($this->customerContact);
            } catch (midcom_error $e) {
                $e->log();
            }
        }
        return null;
    }

    public function __get($property)
    {
        if ($property == 'contacts') {
            if ($this->_contacts === null) {
                $this->get_members();
            }
            return $this->_contacts;
        }
        return parent::__get($property);
    }

    public function _on_creating() : bool
    {
        $this->start = $this->start ?: time();
        $this->state = $this->state ?: self::STATE_ACTIVE;
        $this->owner = $this->owner ?: midcom_connection::get_user();

        return true;
    }

    public function _on_updating() : bool
    {
        if (   $this->state != self::STATE_ACTIVE
            && !$this->end) {
            //Not active anymore and end not set, set it to now
            $this->end = time();
        }
        if (   $this->end
            && $this->state == self::STATE_ACTIVE) {
            //Returned to active state, clear the end marker.
            $this->end = 0;
        }

        return true;
    }

    public function _on_loaded()
    {
        if (empty($this->title)) {
            $this->title = "salesproject #{$this->id}";
        }
    }

    public function _on_updated()
    {
        //Ensure owner can do stuff regardless of other ACLs
        if ($owner_person = midcom::get()->auth->get_user($this->owner)) {
            $this->set_privilege('midgard:read', $owner_person->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:create', $owner_person->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:delete', $owner_person->id, MIDCOM_PRIVILEGE_ALLOW);
            $this->set_privilege('midgard:update', $owner_person->id, MIDCOM_PRIVILEGE_ALLOW);
        }
    }

    /**
     * Populates contacts as resources lists
     */
    function get_members()
    {
        $this->_contacts = [];
        // Make sure primary contact comes out on top
        if ($this->customerContact) {
            $this->_contacts[$this->customerContact] = true;
        }
        if ($this->id) {
            $mc = org_openpsa_projects_role_dba::new_collector('project', $this->id);
            $mc->add_constraint('role', '=', self::ROLE_MEMBER);

            $this->_contacts += array_fill_keys($mc->get_values('person'), true);
        }
    }

    /**
     * Marks the salesproject as delivered if no active or pending deliverables are left
     */
    public function mark_delivered()
    {
        $this->mark(self::STATE_DELIVERED);
    }

    /**
     * Marks the salesproject as invoiced if no pending deliverables are left
     */
    public function mark_invoiced()
    {
        $this->mark(self::STATE_INVOICED);
    }

    private function mark(int $state)
    {
        if ($this->state >= $state) {
            return;
        }

        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->id);
        $mc->add_constraint('state', '<', $state);
        $mc->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
        $mc->execute();

        if ($mc->count() == 0) {
            $this->state = $state;
            $this->update();
        }
    }
}
