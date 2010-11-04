<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id$
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching person position information from Yahoo! Fire Eagle
 * @package org.routamc.positioning
 */
class org_routamc_positioning_cron_fetchfireeagle extends midcom_baseclasses_components_cron_handler
{
    function _on_initialize()
    {
        return true;
    }

    /**
     * Fetches Fire Eagle information for users
     */
    function _on_execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('_on_execute called');

        $fireeagle = org_routamc_positioning_importer::create('fireeagle');
        $fireeagle->seek_fireeagle_users();

        debug_add('Done');
        debug_pop();
        return;
    }
}
?>