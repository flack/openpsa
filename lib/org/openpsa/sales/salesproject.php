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
        'org_openpsa_sales_salesproject_member_dba' => 'salesproject'
    );

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
        $deliverable_qb->add_constraint('state', '<>', ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED);
        $deliverables = $deliverable_qb->execute();
        foreach ($deliverables as $deliverable)
        {
            if ($deliverable->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
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

    function generate_salesproject_number()
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
                    if ($object->status >= ORG_OPENPSA_TASKSTATUS_COMPLETED)
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
        return;
    }

    public function __get($property)
    {
        if ($property == 'contacts')
        {
            if(is_null($this->_contacts))
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
            $this->status = ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE;
        }
        if (!$this->owner)
        {
            $this->owner = midcom_connection::get_user();
        }
        return true;
    }

    public function _on_updating()
    {
        if (   $this->status != ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE
            && !$this->end)
        {
            //Not active anymore and end not set, set it to now
            $this->end = time();
        }
        if (   $this->end
            && $this->status == ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE)
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
        return $_MIDCOM->auth->get_user($pid);
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

        $mc = org_openpsa_sales_salesproject_member_dba::new_collector('salesproject', $this->id);
        $mc->add_value_property('person');
        $mc->execute();

        $ret = $mc->list_keys();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach (array_keys($ret) as $guid)
            {
                $this->_contacts[$mc->get_subkey($guid, 'person')] = true;
            }
        }

        return true;
    }

    function get_parent_guid_uncached()
    {
        if ($this->up != 0)
        {
            $parent = new org_openpsa_sales_salesproject_dba($this->up);
            return $parent->guid;
        }
        else
        {
            return null;
        }
    }

    /**
     * Marks the salesproject as delivered if no active or pending deliverables are left
     */
    public function mark_delivered()
    {
        if ($this->status >= ORG_OPENPSA_SALESPROJECTSTATUS_DELIVERED)
        {
            return;
        }

        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->id);
        $mc->add_constraint('state', '<', ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DELIVERED);
        $mc->add_constraint('state', '<>', ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED);
        $mc->execute();

        if ($mc->count() == 0)
        {
            $this->status = ORG_OPENPSA_SALESPROJECTSTATUS_DELIVERED;
            $this->update();
        }
    }

    /**
     * Marks the salesproject as invoiced if no pending deliverables are left
     */
    public function mark_invoiced()
    {
        if ($this->status >= ORG_OPENPSA_SALESPROJECTSTATUS_INVOICED)
        {
            return;
        }

        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->id);
        $mc->add_constraint('state', '<', ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_INVOICED);
        $mc->add_constraint('state', '<>', ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED);
        $mc->execute();

        if ($mc->count() == 0)
        {
            $this->status = ORG_OPENPSA_SALESPROJECTSTATUS_INVOICED;
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