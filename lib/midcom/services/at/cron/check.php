<?php
/**
 * @package midcom.services.at
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The Cron handler of the AT service, when executed it checks the database for entries
 * that need to be run, then loads their relevant components and calls the interface
 * class for the defined method.
 *
 * @package midcom.services.at
 */
class midcom_services_at_cron_check extends midcom_baseclasses_components_cron_handler
{
    /**
     * Loads all entries that need to be processed and processes them.
     *
     * @todo: refactor to use more modern MidCOM interfaces and better sanity-checking
     */
    public function _on_execute()
    {
        $qb = midcom_services_at_entry_dba::new_query_builder();
        $qb->add_constraint('start', '<=', time());
        $qb->add_constraint('status', '=', midcom_services_at_entry_dba::SCHEDULED);
        $qb->set_limit((int) $this->_config->get('limit_per_run'));

        midcom::get()->auth->request_sudo('midcom.services.at');
        $qbret = $qb->execute();
        midcom::get()->auth->drop_sudo();

        foreach ($qbret as $entry) {
            debug_add("Processing entry #{$entry->id}\n");
            //Avoid double-execute in case of long runs
            $entry->status = midcom_services_at_entry_dba::RUNNING;
            midcom::get()->auth->request_sudo('midcom.services.at');
            $entry->update();
            midcom::get()->auth->drop_sudo();
            midcom::get()->componentloader->load($entry->component);
            $args = $entry->arguments;
            $args['midcom_services_at_entry_object'] = $entry;
            $interface = midcom::get()->componentloader->get_interface_class($entry->component);
            $method = $entry->method;
            if (!is_callable(array($interface, $method))) {
                $error = get_class($interface) . "->{$method}() is not callable";
                $this->handle_error($entry, $error, $args);
                continue;
            }
            $mret = $interface->$method($args, $this);

            if ($mret !== true) {
                $error = get_class($interface) . '->' . $method . '(' . json_encode($args) . ", \$this) returned '{$mret}', errstr: " . midcom_connection::get_error_string();
                $this->handle_error($entry, $error, $args);
            } else {
                midcom::get()->auth->request_sudo('midcom.services.at');
                $entry->delete();
                midcom::get()->auth->drop_sudo();
            }
        }
    }

    private function handle_error(midcom_services_at_entry_dba $entry, $error, array $args)
    {
        $this->print_error($error, $args);
        //PONDER: Delete instead ? (There is currently nothing we do with failed entries)
        $entry->status = midcom_services_at_entry_dba::FAILED;
        midcom::get()->auth->request_sudo('midcom.services.at');
        $entry->update();
        midcom::get()->auth->drop_sudo();
    }
}
