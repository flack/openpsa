<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: fetchicbm.php 3757 2006-07-27 14:32:42Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching person position information from Qaiku
 * @package org.routamc.positioning
 */
class org_routamc_positioning_cron_fetchqaiku extends midcom_baseclasses_components_cron_handler
{
    function _on_initialize()
    {
        return true;
    }

    /**
     * Fetches icbm information for users
     */
    function _on_execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('_on_execute called');

        $html = org_routamc_positioning_importer::create('qaiku');
        $html->seek_qaiku_users();

        debug_add('Done');
        debug_pop();
        return;
    }
}
?>