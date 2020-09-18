<?php
/**
 * @package midcom.services.at
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * At service library, this interface class is used to register jobs to the service.
 *
 * <b>Job registration</b>
 *
 * Example callback:
 * <code>
 *  function cron_callback_method($args, &$handler)
 *  {
 *      $handler->print_error('got args:', $args);
 *      return true;
 *  }
 * </code>
 *
 * Example job registration (for the method above):
 * <code>
 * midcom_services_at_interface::register(
 *   time() + 120,
 *   'org.openpsa.directmarketing',
 *   'cron_callback_method',
 *   array
 *   (
 *      'foo' => 'bar',
 *   ));
 * </code>
 *
 * @package midcom.services.at
 */
class midcom_services_at_interface extends midcom_baseclasses_components_interface
{
    /**
     * Registers a job to the AT service.
     *
     * @param int $start Timestamp after which the job is run
     * @param string $component The name of the component which should run the job
     * @param string $method The method in interface class to call to run the job
     * @param array $args Arguments array for the method
     * @return boolean Indicating success/failure in registering the job
     */
    public static function register(int $start, string $component, string $method, array $args) : bool
    {
        $entry = new midcom_services_at_entry_dba();
        $entry->start = $start;
        $entry->component = $component;
        $entry->method = $method;
        $entry->arguments = $args;
        return $entry->create();
    }
}
