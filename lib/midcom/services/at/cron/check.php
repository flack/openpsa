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
     */
    public function execute()
    {
        $limit = (int) $this->_config->get('limit_per_run');

        // We load each entry separately to minimize the chances of double executions
        // when cron runs overlap or are triggered twice for some reason
        // since this is only used by cron, performance is secondary, so better play it safe
        for ($i = 0; $i < $limit; $i++) {
            $qb = midcom_services_at_entry_dba::new_query_builder();
            $qb->add_constraint('start', '<=', time());
            $qb->add_constraint('status', '=', midcom_services_at_entry_dba::SCHEDULED);
            $qb->set_limit(1);

            midcom::get()->auth->request_sudo($this->_component);
            $qbret = $qb->execute();
            midcom::get()->auth->drop_sudo();
            if (empty($qbret)) {
                break;
            }
            $this->process_entry($qbret[0]);
        }
    }

    private function process_entry(midcom_services_at_entry_dba $entry)
    {
        debug_add("Processing entry #{$entry->id}\n");
        //Avoid double-execute
        $entry->status = midcom_services_at_entry_dba::RUNNING;
        midcom::get()->auth->request_sudo($this->_component);
        $entry->update();
        midcom::get()->auth->drop_sudo();
        $args = $entry->arguments;
        $args['midcom_services_at_entry_object'] = $entry;
        $interface = midcom::get()->componentloader->get_interface_class($entry->component);
        $method = $entry->method;
        if (!method_exists($interface, $method)) {
            $error = $interface::class . "->{$method}() is not callable";
            $this->handle_error($entry, $error, $args);
            return;
        }
        $mret = $interface->$method($args, $this);

        if ($mret !== true) {
            $error = $interface::class . '->' . $method . '(' . json_encode($args) . ", \$this) returned '{$mret}', errstr: " . midcom_connection::get_error_string();
            $this->handle_error($entry, $error, $args);
        } else {
            midcom::get()->auth->request_sudo($this->_component);
            $entry->delete();
            midcom::get()->auth->drop_sudo();
        }
    }

    private function handle_error(midcom_services_at_entry_dba $entry, string $error, array $args)
    {
        $this->print_error($error, $args);
        //PONDER: Delete instead ? (There is currently nothing we do with failed entries)
        $entry->status = midcom_services_at_entry_dba::FAILED;
        midcom::get()->auth->request_sudo($this->_component);
        $entry->update();
        midcom::get()->auth->drop_sudo();
    }
}
