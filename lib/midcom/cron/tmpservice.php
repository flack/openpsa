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
class midcom_cron_tmpservice extends midcom_baseclasses_components_cron_handler
{
    public function _on_execute()
    {
        $qb = midcom_core_temporary_object::new_query_builder();
        $qb->add_constraint('timestamp', '<', time() - midcom::get()->config->get('midcom_temporary_resource_timeout'));
        $qb->set_limit(500);
        $result = $qb->execute();
        foreach ($result as $tmp)
        {
            if (!$tmp->delete())
            {
                $msg = "Failed to delete temporary object {$tmp->id}, last Midgard error was: " . midcom_connection::get_error_string();
                $this->print_error($msg, $tmp);
            }
            else
            {
                debug_add("Deleted temporary object {$tmp->id}.");
            }
        }
    }
}
