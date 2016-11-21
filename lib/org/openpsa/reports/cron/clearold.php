<?php
/**
 * @package org.openpsa.reports
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for clearing tokens from old send receipts
 * @package org.openpsa.reports
 */
class org_openpsa_reports_cron_clearold extends midcom_baseclasses_components_cron_handler
{
    private $cutoff;

    public function _on_initialize()
    {
        $days = $this->_config->get('temporary_report_max_age');
        if ($days == 0) {
            debug_add('temporary_report_max_age evaluates to zero, aborting');
            return false;
        }

        $this->cutoff = gmstrftime('%Y-%m-%d %T', time() - ($days * 3600 * 24));

        return true;
    }

    /**
     * Find all old temporary reports and clear them.
     */
    public function _on_execute()
    {
        //Disable limits, TODO: think if this could be done in smaller chunks to save memory.
        midcom::get()->disable_limits();
        $qb = org_openpsa_reports_query_dba::new_query_builder();
        $qb->add_constraint('metadata.created', '<', $this->cutoff);
        $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_reports_query_dba::OBTYPE_REPORT_TEMPORARY);
        $ret = $qb->execute_unchecked();

        foreach ($ret as $query) {
            debug_add("removing temporary query #{$query->id}");
            if (!$query->delete()) {
                debug_add("FAILED to delete query #{$query->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }
    }
}
