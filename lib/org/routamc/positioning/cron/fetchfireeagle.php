<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching person position information from Yahoo! Fire Eagle
 * @package org.routamc.positioning
 */
class org_routamc_positioning_cron_fetchfireeagle extends midcom_baseclasses_components_cron_handler
{
    /**
     * Fetches Fire Eagle information for users
     */
    public function _on_execute()
    {
        debug_add('_on_execute called');

        $fireeagle = org_routamc_positioning_importer::create('fireeagle');
        $fireeagle->seek_fireeagle_users();

        debug_add('Done');
    }
}
?>