<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for syncing product group and topic hierarchies
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_cron_groupsync extends midcom_baseclasses_components_cron_handler
{
    /**
     * Process the replication queue
     */
    public function _on_execute()
    {
        if (!$this->_config->get('groupsync_cron_enabled'))
        {
            return;
        }
        $sync_helper = new org_openpsa_products_groupsync();
        $sync_helper->full_sync();
    }
}
?>