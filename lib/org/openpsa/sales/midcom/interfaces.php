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
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object) : ?string
    {
        if ($object instanceof org_openpsa_sales_salesproject_dba) {
            return "salesproject/{$object->guid}/";
        }
        if ($object instanceof org_openpsa_sales_salesproject_deliverable_dba) {
            return "deliverable/{$object->guid}/";
        }
        return null;
    }

    public function _on_watched_dba_update(midcom_core_dbaobject $object)
    {
        if ($agreement = $object->get_agreement()) {
            try {
                $agreement = new org_openpsa_sales_salesproject_deliverable_dba($agreement);
                $agreement->update_units();
            } catch (midcom_error $e) {
                $e->log();
            }
        }
    }

    /**
     * AT handler for handling subscription cycles.
     */
    public function new_subscription_cycle(array $args, midcom_baseclasses_components_cron_handler $handler)
    {
        if (!isset($args['deliverable'], $args['cycle'])) {
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
