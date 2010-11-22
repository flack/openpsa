<?php
/**
 * @package midcom.helper.replicator
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: groupsync.php 22916 2009-07-15 09:53:28Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for processing the replication queue
 * @package midcom.helper.replicator
 */
class org_openpsa_products_cron_groupsync extends midcom_baseclasses_components_cron_handler
{
    /**
     * Process the replication queue
     */
    function _on_execute()
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