<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for cleaning up the database of old entries. Disabled by default in configuration.
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_cron_cleanup extends midcom_baseclasses_components_cron_handler
{
    /**
     * Find all old entries and delete them.
     */
    public function _on_execute()
    {
        if (!$this->_config->get('delete_older'))
        {
            return;
        }
        debug_add('_on_execute called');

        if (!midcom::get('auth')->request_sudo('org.openpsa.directmarketing'))
        {
            $msg = "Could not get sudo, aborting operation, see error log for details";
            $this->print_error($msg);
            debug_add($msg, MIDCOM_LOG_ERROR);
            return;
        }

        $cleanup = new org_openpsa_directmarketing_cleanup();
        $cleanup->delete();

        midcom::get('auth')->drop_sudo();

        debug_add('Done');
        return;
    }
}
?>