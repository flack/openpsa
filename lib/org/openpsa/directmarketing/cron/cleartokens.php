<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: cleartokens.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for clearing tokens from old send receipts
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_cron_cleartokens extends midcom_baseclasses_components_cron_handler
{
    function _on_initialize()
    {
        return true;
    }

    /**
     * Find all old send tokens and clear them.
     */
    function _on_execute()
    {
        //Disable limits, TODO: think if this could be done in smaller chunks to save memory.
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('_on_execute called');
        $days = $this->_config->get('send_token_max_age');
        if ($days == 0)
        {
            debug_add('send_token_max_age evaluates to zero, aborting');
            debug_pop();
            return;
        }

        $th = time() - ($days * 3600 * 24);
        $qb = org_openpsa_directmarketing_campaign_message_receipt_dba::new_query_builder();
        $qb->add_constraint('token', '<>', '');
        $qb->add_constraint('timestamp', '<', $th);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_MESSAGERECEIPT_SENT);
        $ret = $qb->execute_unchecked();
        if (   $ret === false
            || !is_array($ret))
        {
            //TODO: display some error ?
            debug_pop();
            return false;
        }
        if (empty($ret))
        {
            debug_add('No results, returning early.');
            debug_pop();
            return;
        }
        foreach ($ret as $receipt)
        {
            debug_add("clearing token '{$receipt->token}' from receipt #{$receipt->id}");
            $receipt->token = '';
            $stat = $receipt->update();
            if (!$stat)
            {
                debug_add("FAILED to update receipt #{$receipt->id}, errstr: " . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            }
        }

        debug_add('Done');
        debug_pop();
        return;
    }
}
?>