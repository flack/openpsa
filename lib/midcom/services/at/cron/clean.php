<?php
/**
 * @package midcom.services.at
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Clears dangling "running"/failed entries
 *
 * @package midcom.services.at
 */
class midcom_services_at_cron_clean extends midcom_baseclasses_components_cron_handler
{
    /**
     * Loads all entries that need to be processed and processes them.
     */
    public function _on_execute()
    {
        debug_add('_on_execute called');

        $qb = midcom_services_at_entry_dba::new_query_builder();
        // (to be) start(ed) AND last touched over two days ago
        $qb->add_constraint('start', '<=', time() - 3600 * 24 * 2);
        $qb->begin_group('OR');
            $qb->add_constraint('host', '=', $_MIDGARD['host']);
            $qb->add_constraint('host', '=', 0);
        $qb->end_group();
        $qb->add_constraint('metadata.revised', '<=', date('Y-m-d H:i:s', time() - 3600 * 24 * 2));
        $qb->add_constraint('status', '>=', midcom_services_at_entry_dba::RUNNING);
        debug_add('Executing QB');
        $_MIDCOM->auth->request_sudo('midcom.services.at');
        $qbret = $qb->execute();
        if (empty($qbret))
        {
            debug_add('Got empty resultset, exiting');
            return;
        }
        debug_add('Processing results');
        foreach($qbret as $entry)
        {
            debug_add("Deleting dangling entry #{$entry->id}\n", MIDCOM_LOG_INFO);
            debug_print_r("Entry #{$entry->id} dump: ", $entry);
            $entry->delete();
        }
        $_MIDCOM->auth->drop_sudo();
        debug_add('Done');
        return;
    }
}
?>