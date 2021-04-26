<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for clearing tokens from old send receipts
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_cron_cleartokens extends midcom_baseclasses_components_cron_handler
{
    private $cutoff;

    public function _on_initialize() : bool
    {
        $days = $this->_config->get('send_token_max_age');
        if ($days == 0) {
            debug_add('send_token_max_age evaluates to zero, aborting');
            return false;
        }
        $this->cutoff = time() - ($days * 3600 * 24);

        return true;
    }

    /**
     * Find all old send tokens and clear them.
     */
    public function execute()
    {
        //Disable limits, TODO: think if this could be done in smaller chunks to save memory.
        midcom::get()->disable_limits();

        $qb = org_openpsa_directmarketing_campaign_messagereceipt_dba::new_query_builder();
        $qb->add_constraint('token', '<>', '');
        $qb->add_constraint('metadata.created', '<', $this->cutoff);
        $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_messagereceipt_dba::SENT);
        $ret = $qb->execute_unchecked();

        foreach ($ret as $receipt) {
            debug_add("clearing token '{$receipt->token}' from receipt #{$receipt->id}");
            $receipt->token = '';
            if (!$receipt->update()) {
                debug_add("FAILED to update receipt #{$receipt->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }
    }
}
