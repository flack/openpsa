<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: fetchicbm.php 3757 2006-07-27 14:32:42Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching person position information from InstaMapper
 * @package org.routamc.positioning
 */
class org_routamc_positioning_cron_fetchinstamapper extends midcom_baseclasses_components_cron_handler
{
    /**
     * Fetches icbm information for users
     */
    function _on_execute()
    {
        debug_add('_on_execute called');

        $html = org_routamc_positioning_importer::create('instamapper');
        $html->seek_instamapper_users();

        debug_add('Done');
        return;
    }
}
?>