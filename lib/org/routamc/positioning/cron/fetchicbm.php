<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching person position information from icbm URLs
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_cron_fetchicbm extends midcom_baseclasses_components_cron_handler
{
    /**
     * Fetches icbm information for users
     */
    public function _on_execute()
    {
        $html = org_routamc_positioning_importer::create('html');
        $html->seek_icbm_users();
    }
}
