<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.directmarketing NAP interface class.
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_navigation extends midcom_baseclasses_components_navigation
{
    public function get_leaves()
    {
        $leaves = array();

        $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->id);
        $qb->add_constraint('archived', '=', 0);
        $qb->add_order('metadata.created', $this->_config->get('navi_order'));
        $campaigns = $qb->execute();

        foreach ($campaigns as $campaign) {
            $leaves["campaign_{$campaign->id}"] = array
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
