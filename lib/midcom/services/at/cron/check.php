<?php
/**
 * @package midcom.services.at
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: check.php 26654 2010-09-20 11:52:41Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * The Cron handler of the AT service, when executed it checks the database for entries
 * that need to be run, then loads their relevant components and calls the interface
 * class statically for the defined method.
 * @package midcom.services.at
 */
class midcom_services_at_cron_check extends midcom_baseclasses_components_cron_handler
{
    /**
     * Loads all entries that need to be processed and processes them.
     *
     * @todo FIXME: refactor to use more modern MidCOM interfaces and better sanity-checking
     */
    function _on_execute()
    {
        debug_add('_on_execute called');

        $qb = midcom_services_at_entry::new_query_builder();
        $qb->add_constraint('start', '<=', time());
        $qb->begin_group('OR');
            $qb->add_constraint('host', '=', $_MIDGARD['host']);
            $qb->add_constraint('host', '=', 0);
        $qb->end_group();
        $qb->add_constraint('status', '=', MIDCOM_SERVICES_AT_STATUS_SCHEDULED);
        $qb->set_limit((int) $this->_config->get('limit_per_run'));
        debug_add('Executing QB');
        $_MIDCOM->auth->request_sudo('midcom.services.at');
        $qbret = $qb->execute();
        $_MIDCOM->auth->drop_sudo();
        if (empty($qbret))
        {
            debug_add('Got empty resultset, exiting');
            return;
        }
        debug_add('Processing results');
        foreach($qbret as $entry)
        {
            debug_add("Processing entry #{$entry->id}\n");
            //Avoid double-execute in case of long runs
            $entry->status = MIDCOM_SERVICES_AT_STATUS_RUNNING;
            $_MIDCOM->auth->request_sudo('midcom.services.at');
            $entry->update();
            $_MIDCOM->auth->drop_sudo();
            $_MIDCOM->componentloader->load($entry->component);
            $args = $entry->arguments;
            $args['midcom_services_at_entry_object'] = $entry;
            $interface = $_MIDCOM->componentloader->get_interface_class($entry->component);
            $method = $entry->method;
            if (!is_callable(array($interface, $method)))
            {
                $error = "\$interface->{$method}() is not callable()";
                $this->print_error($error);
                debug_add($error, MIDCOM_LOG_ERROR);
                debug_add('$interface is ' . get_class($interface));
                debug_print_r('$args', $args);
                //PONDER: Delete instead ? (There is currently nothing we do with failed entries)
                $entry->status = MIDCOM_SERVICES_AT_STATUS_FAILED;
                $_MIDCOM->auth->request_sudo('midcom.services.at');
                $entry->update();
                $_MIDCOM->auth->drop_sudo();
                continue;
            }
            $mret = $interface->$method($args, $this);
            if ($mret !== true)
            {
                $error = "\$interface->{$method}(\$args, \$this) returned '{$mret}', errstr: " . midcom_connection::get_error_string();
                $this->print_error($error);
                debug_add($error, MIDCOM_LOG_ERROR);
                debug_add('$interface is ' . get_class($interface));
                debug_print_r('$args', $args);
                //PONDER: Delete instead ? (There is currently nothing we do with failed entries)
                $entry->status = MIDCOM_SERVICES_AT_STATUS_FAILED;
                $_MIDCOM->auth->request_sudo('midcom.services.at');
                $entry->update();
                $_MIDCOM->auth->drop_sudo();
            }
            else
            {
                $_MIDCOM->auth->request_sudo('midcom.services.at');
                $entry->delete();
                $_MIDCOM->auth->drop_sudo();
            }
        }
        debug_add('Done');
        return;
    }
}
?>