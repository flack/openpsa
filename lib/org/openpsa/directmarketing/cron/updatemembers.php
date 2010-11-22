<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: updatemembers.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for updating members of all (not-archived) smart campaigns
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_cron_updatemembers extends midcom_baseclasses_components_cron_handler
{
    /**
     * Loads all (not-archived) smart campaigns and schedules a separate background update for each
     */
    function _on_execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('_on_execute called');
        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('archived', '=', 0);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART);
        $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
        $ret = $qb->execute();
        $_MIDCOM->auth->drop_sudo();
        if (   $ret === false
            || !is_array($ret))
        {
            //TODO: display some error ?
            debug_pop();
            return false;
        }
        if (empty($ret))
        {
            debug_pop();
            return;
        }
        $i=1;
        foreach($ret as $campaign)
        {
            $next_time = time()+(($i++)*60);
            debug_add("Scheduling member update for campaign #{$campaign->id} ({$campaign->title}) to happen on " . date('Y-m-d H:i:s', $next_time));
            $stat = $campaign->schedule_update_smart_campaign_members($next_time);
            if (!$stat)
            {
                //TODO: Display some error ?
            }
        }

        debug_add('Done');
        debug_pop();
        return;
    }
}
?>