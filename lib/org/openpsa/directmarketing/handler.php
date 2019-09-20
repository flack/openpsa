<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package org.openpsa.directmarketing
 */
trait org_openpsa_directmarketing_handler
{
    /**
     * @param string $identifier GUID or ID
     * @throws midcom_error_notfound
     */
    public function load_campaign($identifier) : org_openpsa_directmarketing_campaign_dba
    {
        $campaign = new org_openpsa_directmarketing_campaign_dba($identifier);
        if ($campaign->node != $this->_topic->id) {
            throw new midcom_error_notfound("The campaign {$identifier} was not found.");
        }
        $this->set_active_leaf('campaign_' . $campaign->id);
        return $campaign;
    }
}
