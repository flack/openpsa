<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching person position information from Plazes
 * @package org.routamc.positioning
 */
class org_routamc_positioning_cron_fetchplazes extends midcom_baseclasses_components_cron_handler
{
    /**
     * Fetches Plazes information for users
     */
    public function _on_execute()
    {
        debug_add('_on_execute called');

        $plazes = org_routamc_positioning_importer::create('plazes');
        $plazes->seek_plazes_users();

        debug_add('Done');
        return;
    }
}
?>