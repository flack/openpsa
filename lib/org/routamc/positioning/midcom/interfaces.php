<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Positioning library interface
 *
 * Startup loads main class, which is used for all operations.
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_interface extends midcom_baseclasses_components_interface
{
    public function __construct()
    {
        $this->_autoload_files = array
        (
            'utils.php',
        );
    }

    // TODO: Watchers and cron entries
}
?>