<?php
/**
 * @package org.openpsa.sales
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA Sales management component
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_interface extends midcom_baseclasses_components_interface
{
    public function __construct()
    {
        $this->_autoload_libraries = array
        (
            'org.openpsa.core',
        );
    }

    public function _on_initialize()
    {
        // Load needed data classes
        $_MIDCOM->componentloader->load_graceful('org.openpsa.products');

        //TODO: Check that the loads actually succeeded

        //org.openpsa.sales object types
        define('ORG_OPENPSA_OBTYPE_SALESPROJECT', 10000);
        define('ORG_OPENPSA_OBTYPE_SALESPROJECT_MEMBER', 10500);
        //org.openpsa.sales salesproject statuses
        define('ORG_OPENPSA_SALESPROJECTSTATUS_LOST', 11000);
        define('ORG_OPENPSA_SALESPROJECTSTATUS_CANCELED', 11001);
        define('ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE', 11050);
        define('ORG_OPENPSA_SALESPROJECTSTATUS_WON', 11100);
        define('ORG_OPENPSA_SALESPROJECTSTATUS_DELIVERED', 11200);
        define('ORG_OPENPSA_SALESPROJECTSTATUS_INVOICED', 11300);

        return true;
    }


    public function _on_resolve_permalink($topic, $config, $guid)
    {
        try
        {
            $salesproject = new org_openpsa_sales_salesproject_dba($guid);
            return "salesproject/{$salesproject->guid}/";
        }
        catch (midcom_error $e)
        {
            try
            {
                $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($guid);
                return "deliverable/{$deliverable->guid}/";
            }
            catch (midcom_error $e)
            {
                return null;
            }
        }
    }

    /**
     * Used by org_openpsa_relatedto_suspect::find_links_object to find "related to" information
     *
     * Currently handles persons
     */
    function org_openpsa_relatedto_find_suspects($object, $defaults, &$links_array)
    {
        if (   !is_array($links_array)
            || !is_object($object))
        {
            debug_add('$links_array is not array or $object is not object, make sure you call this correctly', MIDCOM_LOG_ERROR);
            return;
        }

        switch(true)
        {
            case $_MIDCOM->dbfactory->is_a($object, 'midcom_db_person'):
                //List all projects and tasks given person is involved with
                $this->_org_openpsa_relatedto_find_suspects_person($object, $defaults, $links_array);
                break;
            case $_MIDCOM->dbfactory->is_a($object, 'midcom_db_event'):
            case $_MIDCOM->dbfactory->is_a($object, 'org_openpsa_calendar_event_dba'):
                $this->_org_openpsa_relatedto_find_suspects_event($object, $defaults, $links_array);
                break;
                //TODO: groups ? other objects ?
        }
        return;
    }

    /**
     * Used by org_openpsa_relatedto_find_suspects to in case the given object is a person
     *
     * Current rule: all participants of event must be either manager,contact or resource in task
     * that overlaps in time with the event.
     *
     * @todo This is currently broken, because the new role table uses GUID links
     */
    private function _org_openpsa_relatedto_find_suspects_event(&$object, &$defaults, &$links_array)
    {
        debug_add('called');
        if (   !is_array($object->participants)
            || count($object->participants) < 2)
        {
            //We have invalid list or less than two participants, abort
            return;
        }
        $qb = new midgard_query_builder('org_openpsa_salesproject_member');

        // Target sales project starts or ends inside given events window or starts before and ends after
        $qb->begin_group('OR');
            $qb->begin_group('AND');
                $qb->add_constraint('salesproject.start', '>=', $object->start);
                $qb->add_constraint('salesproject.start', '<=', $object->end);
            $qb->end_group();
            $qb->begin_group('AND');
                $qb->add_constraint('salesproject.end', '<=', $object->end);
                $qb->add_constraint('salesproject.end', '>=', $object->start);
            $qb->end_group();
            $qb->begin_group('AND');
                $qb->add_constraint('salesproject.start', '<=', $object->start);
                $qb->begin_group('OR');
                    $qb->add_constraint('salesproject.end', '>=', $object->end);
                    $qb->add_constraint('salesproject.end', '=', 0);
                $qb->end_group();
            $qb->end_group();
        $qb->end_group();

        //Target sales project is active
        $qb->add_constraint('salesproject.status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE);

        //Each event participant is either manager or member (resource/contact) in task
        foreach ($object->participants as $pid => $bool)
        {
            $qb->begin_group('OR');
                $qb->add_constraint('salesproject.owner', '=', $pid);
                $qb->add_constraint('person', '=', $pid);
            $qb->end_group();
        }
        $qbret = @$qb->execute();

        if (!is_array($qbret))
        {
            debug_add('QB returned with error, aborting, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return;
        }
        $seen_tasks = array();
        foreach ($qbret as $resource)
        {
            debug_add("processing resource #{$resource->id}");
            if (isset($seen_tasks[$resource->salesproject]))
            {
                //Only process one task once (someone might be both owner and contact for example)
                continue;
            }
            $seen_tasks[$resource->salesproject] = true;
            $to_array = array('other_obj' => false, 'link' => false);
            $task = new org_openpsa_sales_salesproject_dba($resource->salesproject);
            $link = new org_openpsa_relatedto_dba();
            org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $task);
            $to_array['other_obj'] = $task;
            $to_array['link'] = $link;

            $links_array[] = $to_array;
        }
        debug_add('done');
        return;
    }

    /**
     * Used by org_openpsa_relatedto_find_suspects to in case the given object is a person
     *
     * @todo This needs to be ported to o.o.contacts_role
     */
    private function _org_openpsa_relatedto_find_suspects_person(&$object, &$defaults, &$links_array)
    {
        $qb = new midgard_query_builder('org_openpsa_salesproject_member');
        $qb->add_constraint('person', '=', $object->id);
        $qb->add_constraint('salesproject.status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE);
        $qbret = @$qb->execute();
        $seen_sp = array();
        if (is_array($qbret))
        {
            foreach ($qbret as $member)
            {
                debug_add("processing resource #{$resource->id}");
                if (isset($seen_sp[$member->salesproject]))
                {
                    //Only process one salesproject once (someone might be both resource and contact for example)
                    continue;
                }
                $seen_sp[$resource->salesproject] = true;
                $to_array = array('other_obj' => false, 'link' => false);
                $sp = new org_openpsa_sales_salesproject_dba($member->salesproject);
                $link = new org_openpsa_relatedto_dba();
                org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $sp);
                $to_array['other_obj'] = $sp;
                $to_array['link'] = $link;

                $links_array[] = $to_array;
            }
        }
        $qb2 = org_openpsa_sales_salesproject_dba::new_query_builder();
        $qb2->add_constraint('owner', '=', $object->id);
        $qb2->add_constraint('status', '=', ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE);
        $qb2ret = @$qb2->execute();
        if (is_array($qb2ret))
        {
            foreach ($qb2ret as $sp)
            {
                debug_add("processing salesproject #{$sp->id}");
                if (isset($seen_sp[$sp->id]))
                {
                    //Only process one task once (someone might be both resource and contact for example)
                    continue;
                }
                $seen_sp[$sp->id] = true;
                $to_array = array('other_obj' => false, 'link' => false);
                $link = new org_openpsa_relatedto_dba();
                org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $sp);
                $to_array['other_obj'] = $sp;
                $to_array['link'] = $link;

                $links_array[] = $to_array;
            }
        }
    }

    /**
     * AT handler for handling subscription cycles.
     *
     * @param array $args handler arguments
     * @param object &$handler reference to the cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    function new_subscription_cycle($args, &$handler)
    {
        if (   !isset($args['deliverable'])
            || !isset($args['cycle']))
        {
            $msg = 'deliverable GUID or cycle number not set, aborting';
            $handler->print_error($msg);
            debug_add($msg, MIDCOM_LOG_ERROR);
            return false;
        }

        try
        {
            $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args['deliverable']);
        }
        catch (midcom_error $e)
        {
            $msg = "Deliverable {$args['deliverable']} not found, error " . midcom_connection::get_error_string();
            $handler->print_error($msg);
            debug_add($msg, MIDCOM_LOG_ERROR);
            return false;
        }
        $scheduler = new org_openpsa_invoices_scheduler($deliverable);

        return $scheduler->run_cycle($args['cycle']);
    }

    /**
     * function to send a notification to owner of the deliverable - guid of deliverable is passed
     */
    function new_notification_message($args , &$handler)
    {
        if (!isset($args['deliverable']))
        {
            $msg = 'deliverable GUID not set, aborting';
            debug_add($msg, MIDCOM_LOG_ERROR);
            $handler->print_error($msg);
            return false;
        }
        try
        {
            $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args['deliverable']);
        }
        catch (midcom_error $e)
        {
            $msg = 'no deliverable with passed GUID:' . $args['deliverable'] . ' , aborting';
            debug_add($msg, MIDCOM_LOG_ERROR);
            $handler->print_error($msg);
            return false;
        }

        $notify_msg = $deliverable->title;
        //get the owner of the sales-project the deliverable belongs to
        try
        {
            $project = new org_openpsa_sales_salesproject_dba($deliverable->salesproject);
        }
        catch (midcom_error $e)
        {
            $msg = 'no project(id:' . $deliverable->salesproject . ') found for deliverable with passed GUID:' . $args['deliverable'] . ' , aborting';
            debug_add($msg, MIDCOM_LOG_ERROR);
            $handler->print_error($msg);
            return false;
        }
        $_MIDCOM->load_library('org.openpsa.notifications');

        return org_openpsa_notifications::notify('org.openpsa.sales:new_notification_message', $project->owner, $notify_msg);
    }
}
?>