<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: loginservice.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.services
 */
class midcom_cron_loginservice extends midcom_baseclasses_components_cron_handler
{
    function _on_execute()
    {
        debug_add('called!');
        $_MIDCOM->dbclassloader->load_classes('midcom', 'core_classes.inc', null, true);

        $qb = new midgard_query_builder('midcom_core_login_session_db');
        $qb->add_constraint('timestamp', '<', time() - $GLOBALS['midcom_config']['auth_login_session_timeout']);
        $qb->set_limit(500);
        $result = $qb->execute();
        foreach ($result as $tmp)
        {
            if (! $tmp->delete())
            {
                // Print and log error
                $msg = "Failed to delete login session {$tmp->id}, last Midgard error was: " . midcom_connection::get_error_string();
                $this->print_error($msg);
                debug_add($msg, MIDCOM_LOG_ERROR);
                debug_print_r('Tried to delete this object:', $tmp);
            }
            else
            {
                if (method_exists($tmp, 'purge'))
                {
                    $tmp->purge();
                }
                debug_add("Deleted login session {$tmp->id}.");
            }
        }
    }
}
?>