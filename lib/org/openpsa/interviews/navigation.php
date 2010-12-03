<?php
/**
 * @package org.openpsa.interviews
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.interviews NAP interface class.
 *
 * @package org.openpsa.interviews
 */
class org_openpsa_interviews_navigation extends midcom_baseclasses_components_navigation
{
    function get_leaves()
    {
        $leaves = array();
        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('archived', '=', 0);
        $campaigns = $qb->execute();
        if (empty($campaigns))
        {
            return $leaves;
        }
        foreach ($campaigns as $campaign)
        {
            $leaves[$campaign->id] = array
            (
                MIDCOM_NAV_URL => "campaign/{$campaign->guid}/",
                MIDCOM_NAV_NAME => $campaign->title,
                MIDCOM_NAV_GUID => $campaign->guid,
                MIDCOM_NAV_OBJECT => $campaign,
            );
        }
        return $leaves;
    }
}
?>