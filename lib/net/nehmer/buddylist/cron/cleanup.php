<?php
/**
 * @package net.nehmer.buddylist
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cleanup Cronjob Handler
 *
 * - Invoked by daily by the MidCOM Cron Service
 * - Cleans up all entries older then the amount of days specified in the configuration.
 *
 * @package net.nehmer.buddylist
 */
class net_nehmer_buddylist_cron_cleanup extends midcom_baseclasses_components_cron_handler
{
    public function _on_execute()
    {
        $timeout_days = $this->_config->get('expiration_days');
        $timeout = time() - ($timeout_days * 86400);

        debug_add("Searching for records with a created timetamp before {$timeout}.");

        $qb = net_nehmer_buddylist_entry::new_query_builder();
        $qb->add_constraint('published', '<', $timeout);
        $result = $qb->execute();

        if ($result)
        {
            foreach ($result as $entry)
            {
                debug_add("Dropping old marketplace entry ID {$entry->id}", MIDCOM_LOG_INFO);
                debug_print_r('Object Dump:', $entry);
                if (! $entry->delete())
                {
                    debug_add("Failed to delete the old marketplace ID {$entry->id}.", MIDCOM_LOG_WARN);
                }
            }
        }
        else
        {
            debug_add('Found none.');
        }
    }
}
?>