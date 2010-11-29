<?php
/**
 * @package org.openpsa.reports
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: clearold.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for clearing tokens from old send receipts
 * @package org.openpsa.reports
 */
class org_openpsa_reports_cron_clearold extends midcom_baseclasses_components_cron_handler
{
    /**
     * Find all old temporary reports and clear them.
     */
    public function _on_execute()
    {
        //Disable limits, TODO: think if this could be done in smaller chunks to save memory.
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
        debug_add('_on_execute called');
        $days = $this->_config->get('temporary_report_max_age');
        if ($days == 0)
        {
            debug_add('temporary_report_max_age evaluates to zero, aborting');
            return;
        }

        $th = time() - ($days * 3600 * 24);
        $qb = org_openpsa_reports_query_dba::new_query_builder();
        $qb->add_constraint('metadata.created', '<', $th);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_REPORT_TEMPORARY);
        $ret = $qb->execute_unchecked();
        if (   $ret === false
            || !is_array($ret))
        {
            //TODO: display some error ?
            return false;
        }
        if (empty($ret))
        {
            debug_add('No results, returning early.');
            return;
        }
        foreach ($ret as $query)
        {
            debug_add("removing temporary query #{$query->id}");
            $stat = $query->delete();
            if (!$stat)
            {
                debug_add("FAILED to delete query #{$query->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }

        debug_add('Done');
        return;
    }
}
?>