<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA direct marketing and mass mailing component
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    public function _on_watched_dba_delete($object)
    {
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        if (   is_a($object, 'midcom_db_person')
            || is_a($object, 'org_openpsa_contacts_person_dba'))
        {
            $qb->add_constraint('person', '=', $object->id);
        }
        else if (is_a($object, 'org_openpsa_directmarketing_campaign_dba'))
        {
            $qb->add_constraint('campaign', '=', $object->id);
        }
        else
        {
            return;
        }

        midcom::get()->auth->request_sudo($this->_component);
        $memberships = $qb->execute();
        foreach ($memberships as $membership)
        {
            $membership->delete();
        }
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Background message sending AT batch handler
     *
     * @param array $args handler arguments
     * @param midcom_baseclasses_components_cron_handler $handler cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    function background_send_message(array $args, midcom_baseclasses_components_cron_handler $handler)
    {
        if (   !isset($args['url_base'])
            || !isset($args['batch']))
        {
            $handler->print_error('url_base or batch number not set, aborting');
            return false;
        }
        midcom::get()->auth->request_sudo($this->_component);

        $batch_url = "{$args['url_base']}/{$args['batch']}/{$args['midcom_services_at_entry_object']->guid}/";
        debug_add("batch_url: {$batch_url}");

        ob_start();
        try
        {
            midcom::get()->dynamic_load($batch_url);
            $ret = true;
        }
        catch (midcom_error $e)
        {
            $ret = $e->getMessage();
        }
        ob_end_clean();

        midcom::get()->auth->drop_sudo();
        return $ret;
    }

    /**
     * For updating smart campaigns members in background
     *
     * @param array $args handler arguments
     * @param midcom_baseclasses_components_cron_handler $handler cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    public function background_update_campaign_members(array $args, midcom_baseclasses_components_cron_handler $handler)
    {
        if (!array_key_exists('campaign_guid', $args))
        {
            $handler->print_error('Campaign GUID not found in arguments list');
            return false;
        }

        midcom::get()->auth->request_sudo($this->_component);
        try
        {
            $campaign = new org_openpsa_directmarketing_campaign_dba($args['campaign_guid']);
        }
        catch (midcom_error $e)
        {
            $handler->print_error("{$args['campaign_guid']} is not a valid campaign GUID");
            return false;
        }

        if (!$campaign->update_smart_campaign_members())
        {
            $handler->print_error('Error while calling campaign->update_smart_campaign_members(), see error log for details');
            return false;
        }

        midcom::get()->auth->drop_sudo();
        return true;
    }

    /**
     * @inheritdoc
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_directmarketing_campaign_dba)
        {
            return "campaign/{$object->guid}/";
        }
        if ($object instanceof org_openpsa_directmarketing_campaign_message_dba)
        {
            return "message/{$object->guid}/";
        }
        return null;
    }
}
