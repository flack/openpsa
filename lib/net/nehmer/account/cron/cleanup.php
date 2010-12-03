<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cleanup Cronjob Handler
 *
 * - Invoked by daily by the MidCOM Cron Service
 * - Cleans up all not yet activated accounts, using the global configuration settings (obviously).
 *
 * @package net.nehmer.account
 */
class net_nehmer_account_cron_cleanup extends midcom_baseclasses_components_cron_handler
{
    public function _on_execute()
    {
        $timeout_days = $this->_config->get('account_activation_timeout');
        $timeout_stamp = time() - ($timeout_days * 86400);
        $timeout = strftime('%Y-%m-%d', $timeout_stamp);

        if (!$_MIDCOM->auth->request_sudo('net.nehmer.account'))
        {
            $msg = "Could not get sudo, aborting operation, see error log for details";
            $this->print_error($msg);
            debug_add($msg, MIDCOM_LOG_ERROR);
            return;
        }

        debug_add("Searching for records with an activation hash creation before {$timeout}.");

        // We need to query the parameters table explicitly, as otherwise we'd have to
        // check all persons manually. We need to use a pure Midgard QB here,
        // as Parameter Query support is not yet supported in MidCOM/Midgard
        $query = new midgard_query_builder('midgard_parameter');
        $query->add_constraint('domain', '=', 'net.nehmer.account');
        $query->add_constraint('name', '=', 'activation_hash_created');
        $query->add_constraint('value', '<', $timeout);
        $result = @$query->execute();

        if ($result)
        {
            foreach ($result as $parameter)
            {
                $person = new midcom_db_person($parameter->parentguid);
                if (   !$person
                    || !$person->guid)
                {
                    debug_add("Failed to open the Person record ID {$parameter->parentguid}, skipping it.", MIDCOM_LOG_WARN);
                    continue;
                }
                debug_add("Dropping not activated account ID {$person->id}", MIDCOM_LOG_INFO);
                debug_print_r('Object Dump:', $person);
                if (! $person->delete())
                {
                    debug_add("Failed to delete the Person record ID {$parameter->parentguid}.", MIDCOM_LOG_WARN);
                }
            }
        }
        else
        {
            debug_add('Found none.');
        }
        $_MIDCOM->auth->drop_sudo();
    }
}
?>