<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.georss GNU Lesser General Public License
 */

/**
 * Cron handler for fetching person position information from GeoRSS URLs
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_cron_fetchgeorss extends midcom_baseclasses_components_cron_handler
{
    /**
     * Fetches georss information for users
     */
    public function _on_execute()
    {
        $georss = org_routamc_positioning_importer::create('georss');
        $georss->seek_georss_users();
    }
}
