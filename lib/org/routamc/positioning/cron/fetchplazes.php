<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: fetchplazes.php 4795 2006-12-19 13:42:39Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching person position information from Plazes
 * @package org.routamc.positioning
 */
class org_routamc_positioning_cron_fetchplazes extends midcom_baseclasses_components_cron_handler
{
    function _on_initialize()
    {
        return true;
    }

    /**
     * Fetches Plazes information for users
     */
    function _on_execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('_on_execute called');

        $plazes = org_routamc_positioning_importer::create('plazes');
        $plazes->seek_plazes_users();

        debug_add('Done');
        debug_pop();
        return;
    }
}
?>