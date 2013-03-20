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

    //org.openpsa.sales salesproject statuses
    const STATUS_LOST = 11000;
    const STATUS_CANCELED = 11001;
    const STATUS_ACTIVE = 11050;
    const STATUS_WON = 11100;
    const STATUS_DELIVERED = 11200;
    const STATUS_INVOICED = 11300;

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
        parent::refresh();
    }

    /**
     * Calculates the prices of deliverables
     *
     * and adds them up to the salesproject value
     */
    function calculate_price()
    {
        $value = 0;
        $cost = 0;

        $deliverable_qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $deliverable_qb->add_constraint('salesproject', '=', $this->id);
        $deliverable_qb->add_constraint('up', '=', 0);
        $deliverable_qb->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED);
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
                $value = $value + ($deliverable->price * $cycles);
                $cost = $cost + ($deliverable->cost * $cycles);
            }
            else
            {
                $value = $value + $deliverable->price;
                $cost = $cost + $deliverable->cost;
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
        try
        {
            $customer = org_openpsa_contacts_group_dba::get_cached($this->customer);
        }
        catch (midcom_error $e)
        {
            try
            {
                $customer = org_openpsa_contacts_person_dba::get_cached($this->customerContact);
            }
            catch (midcom_error $e)
            {
                $customer = null;
                $e->log();
            }
        }
        return $customer;
    }

    /**
     * Fills the next and previous action properties
     * based on the confirmed relatedto links
     *
     * If optional argument is set only considers actions
     * where said person is involved, NOT IMPLEMENTED
     *
     * @todo Implement $limit_to_person support
     */
    function get_actions($limit_to_person = false)
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
                        if ($object->end < time())
                        {
                            //PONDER: Do something ?
                        }
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
                default:
                    continue 2;
            }
        }
        usort($sort_prev, array('self', '_sort_action_by_time_reverse'));
        usort($sort_next, array('self', '_sort_action_by_time'));
        debug_print_r("sort_next:", $sort_next);
        debug_print_r("sort_prev:", $sort_prev);

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
        if (!$this->start)
        {
            $this->start = time();
        }
        if (!$this->status)
        {
            $this->status = self::STATUS_ACTIVE;
        }
        if (!$this->owner)
        {
            $this->owner = midcom_connection::get_user();
        }
        return true;
    }

    public function _on_updating()
    {
        if (   $this->status != self::STATUS_ACTIVE
            && !$this->end)
        {
            //Not active anymore and end not set, set it to now
            $this->end = time();
        }
        if (   $this->end
            && $this->status == self::STATUS_ACTIVE)
        {
            //Returned to active status, clear the end marker.
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

    private function _pid_to_obj($pid)
    {
        return midcom::get('auth')->get_user($pid);
    }

    public function _on_updated()
    {
        //Ensure owner can do stuff regardless of other ACLs
        if ($this->owner)
        {
            $owner_person = $this->_pid_to_obj($this->owner);
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
        if (!$this->id)
        {
            return false;
        }

        $this->_contacts = array();

        $mc = org_openpsa_contacts_role_dba::new_collector('objectGuid', $this->guid);
        $mc->add_constraint('role', '=', self::ROLE_MEMBER);

        $members = $mc->get_values('person');

        foreach ($members as $member)
        {
            $this->_contacts[$member] = true;
        }

        if ($this->customerContact)
        {
            $this->_contacts[$this->customerContact] = true;
        }

        return true;
    }

    /**
     * Marks the salesproject as delivered if no active or pending deliverables are left
     */
    public function mark_delivered()
    {
        if ($this->status >= self::STATUS_DELIVERED)
        {
            return;
        }

        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->id);
        $mc->add_constraint('state', '<', org_openpsa_sales_salesproject_deliverable_dba::STATUS_DELIVERED);
        $mc->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED);
        $mc->execute();

        if ($mc->count() == 0)
        {
            $this->status = self::STATUS_DELIVERED;
            $this->update();
        }
    }

    /**
     * Marks the salesproject as invoiced if no pending deliverables are left
     */
    public function mark_invoiced()
    {
        if ($this->status >= self::STATUS_INVOICED)
        {
            return;
        }

        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->id);
        $mc->add_constraint('state', '<', org_openpsa_sales_salesproject_deliverable_dba::STATUS_INVOICED);
        $mc->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED);
        $mc->execute();

        if ($mc->count() == 0)
        {
            $this->status = self::STATUS_INVOICED;
            $this->update();
        }
    }

    /**
     * For sorting arrays in get_actions method
     */
    private static function _sort_action_by_time($a, $b)
    {
        $ap = $a['time'];
        $bp = $b['time'];
        if ($ap > $bp)
        {
            return 1;
        }
        if ($ap < $bp)
        {
            return -1;
        }
        return 0;
    }

    /**
     * For sorting arrays in get_actions method
     */
    private static function _sort_action_by_time_reverse($a, $b)
    {
        $ap = $a['time'];
        $bp = $b['time'];
        if ($ap < $bp)
        {
            return 1;
        }
        if ($ap > $bp)
        {
            return -1;
        }
        return 0;
    }
}
?>
