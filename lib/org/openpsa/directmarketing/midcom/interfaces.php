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
implements midcom_services_permalinks_resolver, org_openpsa_contacts_duplicates_support
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

        midcom::get('auth')->request_sudo($this->_component);
        $memberships = $qb->execute();
        foreach ($memberships as $membership)
        {
            $membership->delete();
        }
        midcom::get('auth')->drop_sudo();
    }

    /**
     * Background message sending AT batch handler
     *
     * @param array $args handler arguments
     * @param object $handler reference to the cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    function background_send_message($args, $handler)
    {
        if (   !isset($args['url_base'])
            || !isset($args['batch']))
        {
            $handler->print_error('url_base or batch number not set, aborting');
            return false;
        }
        midcom::get('auth')->request_sudo($this->_component);

        $batch_url = "{$args['url_base']}/{$args['batch']}/{$args['midcom_services_at_entry_object']->guid}/";
        debug_add("batch_url: {$batch_url}");

        ob_start();
        midcom::get()->dynamic_load($batch_url);
        ob_end_clean();

        midcom::get('auth')->drop_sudo();
        return true;
    }

    /**
     * For updating smart campaigns members in background
     *
     * @param array $args handler arguments
     * @param object $handler reference to the cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    function background_update_campaign_members(array $args, $handler)
    {
        if (!array_key_exists('campaign_guid', $args))
        {
            $handler->print_error('Campaign GUID not found in arguments list');
            return false;
        }

        midcom::get('auth')->request_sudo($this->_component);
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

        midcom::get('auth')->drop_sudo();
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

    public function get_merge_configuration($object_mode, $merge_mode)
    {
        $config = array();
        if ($merge_mode == 'future')
        {
            // DirMar does not have future references so we have nothing to transfer...
            return $config;
        }
        if ($object_mode == 'person')
        {
            $config['org_openpsa_directmarketing_campaign_member_dba'] = array
            (
                'person' => array
                (
                    'target' => 'id',
                    'duplicate_check' => 'check_duplicate_membership'
                )
            );
            $config['org_openpsa_directmarketing_campaign_messagereceipt_dba'] = array
            (
                'person' => array
                (
                    'target' => 'id',
                )
            );
            $config['org_openpsa_directmarketing_link_log_dba'] = array
            (
                'person' => array
                (
                    'target' => 'id',
                )
            );
            $config['org_openpsa_directmarketing_campaign_dba'] = array();
            $config['org_openpsa_directmarketing_campaign_message_dba'] = array();

        }
        return $config;
    }
}
?>