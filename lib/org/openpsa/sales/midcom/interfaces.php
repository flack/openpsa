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
            case midcom::get('dbfactory')->is_a($object, 'midcom_db_person'):
                //List all projects and tasks given person is involved with
                $this->_org_openpsa_relatedto_find_suspects_person($object, $defaults, $links_array);
                break;
            case midcom::get('dbfactory')->is_a($object, 'midcom_db_event'):
            case midcom::get('dbfactory')->is_a($object, 'org_openpsa_calendar_event_dba'):
                $this->_org_openpsa_relatedto_find_suspects_event($object, $defaults, $links_array);
                break;
                //TODO: groups ? other objects ?
        }
    }

    /**
     * Used by org_openpsa_relatedto_find_suspects to in case the given object is a person
     *
     * Current rule: all participants of event must be either manager,contact or resource in task
     * that overlaps in time with the event.
     */
    private function _org_openpsa_relatedto_find_suspects_event(&$object, &$defaults, &$links_array)
    {
        if (   !is_array($object->participants)
            || count($object->participants) < 2)
        {
            //We have invalid list or less than two participants, abort
            return;
        }
        $mc = org_openpsa_contacts_role_dba::new_collector('role', org_openpsa_sales_salesproject_dba::ROLE_MEMBER);
        $mc->add_constraint('person', 'IN', array_keys($object->participants));
        $guids = $mc->get_values('objectGuid');

        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();

        // Target sales project starts or ends inside given events window or starts before and ends after
        $qb->add_constraint('start', '<=', $object->end);
        $qb->begin_group('OR');
            $qb->add_constraint('end', '>=', $object->start);
            $qb->add_constraint('end', '=', 0);
        $qb->end_group();

        //Target sales project is active
        $qb->add_constraint('status', '=', org_openpsa_sales_salesproject_dba::STATUS_ACTIVE);

        //Each event participant is either manager or member (resource/contact) in task
        $qb->begin_group('OR');
            $qb->add_constraint('owner', 'IN', array_keys($object->participants));
            if (!empty($guids))
            {
                $qb->add_constraint('guid', 'IN', $guids);
            }
        $qb->end_group();

        $qbret = @$qb->execute();

        if (!is_array($qbret))
        {
            debug_add('QB returned with error, aborting, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return;
        }
        $seen_tasks = array();
        foreach ($qbret as $salesproject)
        {
            if (isset($seen_tasks[$salesproject->id]))
            {
                //Only process one task once (someone might be both owner and contact for example)
                continue;
            }
            $seen_tasks[$salesproject->id] = true;
            $to_array = array('other_obj' => false, 'link' => false);
            $link = new org_openpsa_relatedto_dba();
            org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $salesproject);
            $to_array['other_obj'] = $salesproject;
            $to_array['link'] = $link;

            $links_array[] = $to_array;
        }
    }

    /**
     * Used by org_openpsa_relatedto_find_suspects to in case the given object is a person
     */
    private function _org_openpsa_relatedto_find_suspects_person(&$object, &$defaults, &$links_array)
    {
        $seen_sp = array();
        $mc = org_openpsa_contacts_role_dba::new_collector('role', org_openpsa_sales_salesproject_dba::ROLE_MEMBER);
        $mc->add_constraint('person', '=', array_keys($object->id));
        $guids = $mc->get_values('objectGuid');

        if (!empty($guids))
        {
            $qb = org_openpsa_sales_salesproject_dba::new_query_builder();
            $qb->add_constraint('status', '=', org_openpsa_sales_salesproject_dba::STATUS_ACTIVE);
            $qb->add_constraint('guid', 'IN', $guids);
            $qbret = @$qb->execute();
            if (is_array($qbret))
            {
                foreach ($qbret as $salesproject)
                {
                    $seen_sp[$salesproject->id] = true;
                    $to_array = array('other_obj' => false, 'link' => false);
                    $link = new org_openpsa_relatedto_dba();
                    org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $salesproject);
                    $to_array['other_obj'] = $salesproject;
                    $to_array['link'] = $link;

                    $links_array[] = $to_array;
                }
            }
        }
        $qb2 = org_openpsa_sales_salesproject_dba::new_query_builder();
        $qb2->add_constraint('owner', '=', $object->id);
        $qb2->add_constraint('status', '=', org_openpsa_sales_salesproject_dba::STATUS_ACTIVE);
        if (!empty($seen_sp))
        {
            $qb2->add_constraint('id', 'NOT IN', array_keys($seen_sp));
        }
        $qb2ret = @$qb2->execute();
        if (is_array($qb2ret))
        {
            foreach ($qb2ret as $sp)
            {
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
     * Function to send a notification to owner of the deliverable - guid of deliverable is passed
     */
    public function new_notification_message($args, &$handler)
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
            $msg = 'no deliverable with passed GUID: ' . $args['deliverable'] . ', aborting';
            debug_add($msg, MIDCOM_LOG_ERROR);
            $handler->print_error($msg);
            return false;
        }

        //get the owner of the salesproject the deliverable belongs to
        try
        {
            $project = new org_openpsa_sales_salesproject_dba($deliverable->salesproject);
        }
        catch (midcom_error $e)
        {
            $msg = 'Failed to load salesproject: ' . $e->getMessage();
            debug_add($msg, MIDCOM_LOG_ERROR);
            $handler->print_error($msg);
            return false;
        }

        $content = sprintf
        (
            $this->_l10n->get('agreement %s ends on %s. click here: %s'),
            $deliverable->title,
            strftime('%x', $deliverable->end),
            midcom::get('permalinks')->create_permalink($deliverable->guid)
        );

        $message = array
        (
            'title' => sprintf($this->_l10n->get('notification for agreement %s'), $deliverable->title),
            'content' => $content,
        );

        return org_openpsa_notifications::notify('org.openpsa.sales:new_notification_message', $project->owner, $message);
    }
}
?>