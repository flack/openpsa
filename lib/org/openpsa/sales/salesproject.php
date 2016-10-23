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
 * @package org.openpsa.sales
 */
class org_openpsa_sales_salesproject_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_salesproject';

    public $autodelete_dependents = array
    (
        'org_openpsa_contacts_role_dba' => 'objectGuid'
    );

    //org.openpsa.sales salesproject states
    const STATE_LOST = 11000;
    const STATE_CANCELED = 11001;
    const STATE_ACTIVE = 11050;
    const STATE_WON = 11100;
    const STATE_DELIVERED = 11200;
    const STATE_INVOICED = 11300;

    //org.openpsa.sales role types
    const ROLE_MEMBER = 10500;

    /**
     * Shorthand access for contact members
     */
    private $_contacts = null;

    /**
     * These two are filled correctly as arrays with the get_actions method
     */
    var $prev_action = false;

    /**
     * These two are filled correctly as arrays with the get_actions method
     */
    var $next_action = false;

    public function refresh()
    {
        $this->_contacts = null;
        return parent::refresh();
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

        $deliverable_qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $deliverable_qb->add_constraint('salesproject', '=', $this->id);
        $deliverable_qb->add_constraint('up', '=', 0);
        $deliverable_qb->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
        $deliverables = $deliverable_qb->execute();
        foreach ($deliverables as $deliverable)
        {
            if ($deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION)
            {
                $scheduler = new org_openpsa_invoices_scheduler($deliverable);
                if ($deliverable->end == 0)
                {
                    // FIXME: Get this from config key 'subscription_profit_months'
                    $cycles = $scheduler->calculate_cycles(12);
                }
                else
                {
                    $cycles = $scheduler->calculate_cycles();
                }
                $value = $value + ($deliverable->price * $cycles) + $deliverable->invoiced;
                $cost = $cost + ($deliverable->cost * $cycles);
            }
            else
            {
                $value = $value + $deliverable->price;
                $cost = $cost + $deliverable->cost;
                if ($deliverable->invoiceByActualUnits)
                {
                    $value = $value + $deliverable->invoiced;
                }
            }
        }
        $profit = $value - $cost;
        if (   $this->value != $value
            || $this->profit != $profit)
        {
            $this->value = $value;
            $this->profit = $value - $cost;
            $this->update();
        }
    }

    public static function generate_salesproject_number()
    {
        // TODO: Make configurable
        $year = date('Y', time());
        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();
        $qb->add_order('metadata.created', 'DESC');
        $qb->add_constraint('start', '>=', mktime(0, 0, 1, 1, 1, $year));
        $previous = $qb->count_unchecked();

        return sprintf('%d-%04d', $year, $previous + 1);
    }

    public function get_project()
    {
        return new org_openpsa_projects_project($this->id);
    }

    public function get_customer()
    {
        if (!empty($this->customer))
        {
            try
            {
                return org_openpsa_contacts_group_dba::get_cached($this->customer);
            }
            catch (midcom_error $e)
            {
                $e->log();
            }
        }
        if (!empty($this->customerContact))
        {
            try
            {
                return org_openpsa_contacts_person_dba::get_cached($this->customerContact);
            }
            catch (midcom_error $e)
            {
                $e->log();
            }
        }
        return null;
    }

    /**
     * Fills the next and previous action properties
     * based on the confirmed relatedto links
     */
    public function get_actions()
    {
        $default = array
        (
            'time'  => false,
            'obj'   => false,
            /* valid types are: noaction, task, event */
            'type'  => 'noaction',
        );
        $this->prev_action = $default;
        $this->next_action = $default;

        $mc = new org_openpsa_relatedto_collector($this->guid, array('org_openpsa_calendar_event_dba', 'org_openpsa_projects_task_dba'));

        $related_objects = $mc->get_related_objects();

        if (count($related_objects) == 0)
        {
            return;
        }

        $sort_prev = array();
        $sort_next = array();

        foreach ($related_objects as $object)
        {
            $to_sort = $default;
            $to_sort['obj'] = $object;

            switch ($object->__mgdschema_class_name__)
            {
                case 'org_openpsa_task':
                    $to_sort['type'] = 'task';
                    if ($object->status >= org_openpsa_projects_task_status_dba::COMPLETED)
                    {
                        $to_sort['time'] = $object->status_time;
                        $sort_prev[] = $to_sort;
                    }
                    else
                    {
                        $to_sort['time'] = $object->end;
                        $sort_next[] = $to_sort;
                    }
                    break;
                case 'org_openpsa_event':
                    $to_sort['type'] = 'event';
                    if ($object->end < time())
                    {
                        $to_sort['time'] = $object->end;
                        $sort_prev[] = $to_sort;
                    }
                    else
                    {
                        $to_sort['time'] = $object->start;
                        $sort_next[] = $to_sort;
                    }
                    break;
            }
        }
        usort($sort_prev, array('self', '_sort_action_by_time_reverse'));
        usort($sort_next, array('self', '_sort_action_by_time'));

        if (isset($sort_next[0]))
        {
            $this->next_action = $sort_next[0];
        }
        if (isset($sort_prev[0]))
        {
            $this->prev_action = $sort_prev[0];
        }
    }

    public function __get($property)
    {
        if ($property == 'contacts')
        {
            if (is_null($this->_contacts))
            {
                $this->get_members();
            }
            return $this->_contacts;
        }
        return parent::__get($property);
    }

    public function _on_creating()
    {
        $this->start = $this->start ?: time();
        $this->state = $this->state ?: self::STATE_ACTIVE;
        $this->owner = $this->owner ?: midcom_connection::get_user();

        return true;
    }

    public function _on_updating()
    {
        if (   $this->state != self::STATE_ACTIVE
            && !$this->end)
        {
            //Not active anymore and end not set, set it to now
            $this->end = time();
        }
        if (   $this->end
            && $this->state == self::STATE_ACTIVE)
        {
            //Returned to active state, clear the end marker.
            $this->end = 0;
        }

        return true;
    }

    public function _on_loaded()
    {
        if (empty($this->title))
        {
            $this->title = "salesproject #{$this->id}";
        }
    }

    public function _on_updated()
    {
        //Ensure owner can do stuff regardless of other ACLs
        if (($owner_person = midcom::get()->auth->get_user($this->owner)))
        {
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
        if ($this->id)
        {
            $mc = org_openpsa_contacts_role_dba::new_collector('objectGuid', $this->guid);
            $mc->add_constraint('role', '=', self::ROLE_MEMBER);

            $this->_contacts = array_fill_keys($mc->get_values('person'), true);

            if ($this->customerContact)
            {
                $this->_contacts[$this->customerContact] = true;
            }
        }
    }

    /**
     * Marks the salesproject as delivered if no active or pending deliverables are left
     */
    public function mark_delivered()
    {
        if ($this->state >= self::STATE_DELIVERED)
        {
            return;
        }

        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->id);
        $mc->add_constraint('state', '<', org_openpsa_sales_salesproject_deliverable_dba::STATE_DELIVERED);
        $mc->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
        $mc->execute();

        if ($mc->count() == 0)
        {
            $this->state = self::STATE_DELIVERED;
            $this->update();
        }
    }

    /**
     * Marks the salesproject as invoiced if no pending deliverables are left
     */
    public function mark_invoiced()
    {
        if ($this->state >= self::STATE_INVOICED)
        {
            return;
        }

        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->id);
        $mc->add_constraint('state', '<', org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED);
        $mc->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
        $mc->execute();

        if ($mc->count() == 0)
        {
            $this->state = self::STATE_INVOICED;
            $this->update();
        }
    }

    /**
     * For sorting arrays in get_actions method
     */
    private static function _sort_action_by_time($a, $b)
    {
        return $a['time'] - $b['time'];
    }

    /**
     * For sorting arrays in get_actions method
     */
    private static function _sort_action_by_time_reverse($a, $b)
    {
        return $b['time'] - $a['time'];
    }
}
