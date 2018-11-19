<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.services
 */
class midcom_cron_loginservice extends midcom_baseclasses_components_cron_handler
{
    public function execute()
    {
        if (midcom::get()->config->get('auth_login_session_timeout')) {
            $qb = new midgard_query_builder('midgard_parameter');
            $qb->add_constraint('domain', '=', 'midcom');
            $qb->add_constraint('name', '=', 'online');
            $qb->add_constraint('value', '<', time() - midcom::get()->config->get('auth_login_session_timeout'));
            foreach ($qb->iterate() as $tmp) {
                if (!$tmp->purge()) {
                    $msg = "Failed to purge login session {$tmp->id}, last Midgard error was: " . midcom_connection::get_error_string();
                    $this->print_error($msg, $tmp);
                } else {
                    debug_add("Purged login session {$tmp->id}.");
                }
            }
        }
    }
}