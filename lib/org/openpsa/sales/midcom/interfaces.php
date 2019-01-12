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
implements midcom_services_permalinks_resolver
{
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_sales_salesproject_dba) {
            return "salesproject/{$object->guid}/";
        }
        if ($object instanceof org_openpsa_sales_salesproject_deliverable_dba) {
            return "deliverable/{$object->guid}/";
        }
        return null;
    }

    /**
     * Used by org_openpsa_relatedto_suspect::find_links_object to find "related to" information
     *
     * Currently handles persons
     */
    public function org_openpsa_relatedto_find_suspects(midcom_core_dbaobject $object, org_openpsa_relatedto_dba $defaults, array &$links_array)
    {
        switch (true) {
            case $object instanceof midcom_db_person:
                //List all projects and tasks given person is involved with
                $this->_find_suspects_person($object, $defaults, $links_array);
                break;
            case $object instanceof org_openpsa_calendar_event_dba:
                $this->_find_suspects_event($object, $defaults, $links_array);
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
    private function _find_suspects_event(midcom_core_dbaobject $object, org_openpsa_relatedto_dba $defaults, array &$links_array)
    {
        if (   !is_array($object->participants)
            || count($object->participants) < 2) {
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
        $qb->add_constraint('state', '=', org_openpsa_sales_salesproject_dba::STATE_ACTIVE);

        //Each event participant is either manager or member (resource/contact) in task
        $qb->begin_group('OR');
        $qb->add_constraint('owner', 'IN', array_keys($object->participants));
        $qb->add_constraint('guid', 'IN', $guids);
        $qb->end_group();

        foreach ($qb->execute() as $salesproject) {
            $to_array = ['other_obj' => false, 'link' => false];
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
    private function _find_suspects_person(midcom_core_dbaobject $object, org_openpsa_relatedto_dba $defaults, array &$links_array)
    {
        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();
        $qb->add_constraint('state', '=', org_openpsa_sales_salesproject_dba::STATE_ACTIVE);
        $qb->begin_group('OR');
            $mc = org_openpsa_contacts_role_dba::new_collector('role', org_openpsa_sales_salesproject_dba::ROLE_MEMBER);
            $mc->add_constraint('person', '=', $object->id);
            $qb->add_constraint('guid', 'IN', $mc->get_values('objectGuid'));
            $qb->add_constraint('owner', '=', $object->id);
        $qb->end_group();

        foreach ($qb->execute() as $salesproject) {
            $link = new org_openpsa_relatedto_dba();
            org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $salesproject);

            $links_array[] = [
                'other_obj' => $salesproject,
                'link' => $link
            ];
        }
    }

    /**
     * AT handler for handling subscription cycles.
     *
     * @param array $args handler arguments
     * @param midcom_baseclasses_components_cron_handler $handler cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    public function new_subscription_cycle(array $args, midcom_baseclasses_components_cron_handler $handler)
    {
        if (   !isset($args['deliverable'])
            || !isset($args['cycle'])) {
            $handler->print_error('deliverable GUID or cycle number not set, aborting');
            return false;
        }

        try {
            $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args['deliverable']);
        } catch (midcom_error $e) {
            $handler->print_error("Deliverable {$args['deliverable']} not found: " . midcom_connection::get_error_string());
            return false;
        }
        $scheduler = new org_openpsa_invoices_scheduler($deliverable);

        return $scheduler->run_cycle($args['cycle']);
    }

    /**
     * Function to send a notification to owner of the deliverable - guid of deliverable is passed
     *
     * @param array $args handler arguments
     * @param midcom_baseclasses_components_cron_handler $handler cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    public function new_notification_message(array $args, midcom_baseclasses_components_cron_handler $handler)
    {
        if (!isset($args['deliverable'])) {
            $handler->print_error('deliverable GUID not set, aborting');
            return false;
        }
        try {
            $deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args['deliverable']);
        } catch (midcom_error $e) {
            $handler->print_error('no deliverable with passed GUID: ' . $args['deliverable'] . ', aborting');
            return false;
        }

        //get the owner of the salesproject the deliverable belongs to
        try {
            $project = new org_openpsa_sales_salesproject_dba($deliverable->salesproject);
        } catch (midcom_error $e) {
            $handler->print_error('Failed to load salesproject: ' . $e->getMessage());
            return false;
        }

        $message = [
            'title' => sprintf($this->_l10n->get('notification for agreement %s'), $deliverable->title),
            'content' => sprintf(
                $this->_l10n->get('agreement %s ends on %s. click here: %s'),
                $deliverable->title,
                $this->_l10n->get_formatter()->date($deliverable->end),
                midcom::get()->permalinks->create_permalink($deliverable->guid)
            )
        ];

        return org_openpsa_notifications::notify('org.openpsa.sales:new_notification_message', $project->owner, $message);
    }
}
