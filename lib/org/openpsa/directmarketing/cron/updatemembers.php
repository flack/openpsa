<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for updating members of all (not-archived) smart campaigns
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_cron_updatemembers extends midcom_baseclasses_components_cron_handler
{
    /**
     * Loads all (not-archived) smart campaigns and schedules a separate background update for each
     */
    public function _on_execute()
    {
        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('archived', '=', 0);
        $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_dba::TYPE_SMART);
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');
        $ret = $qb->execute();
        midcom::get()->auth->drop_sudo();

        $i = 1;
        foreach ($ret as $campaign)
        {
            $next_time = time() + (($i++) * 60);
            debug_add("Scheduling member update for campaign #{$campaign->id} ({$campaign->title}) to happen on " . date('Y-m-d H:i:s', $next_time));
            if (!$campaign->schedule_update_smart_campaign_members($next_time))
            {
                debug_add('schedule_update_smart_campaign_members returned false', MIDCOM_LOG_ERROR);
            }
        }
    }
}
