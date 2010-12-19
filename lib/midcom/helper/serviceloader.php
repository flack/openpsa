<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Service interface loader class
 *
 * MidCOM services are implemented following the inversion of control pattern where services are defined by an
 * interface class and
 * @package midcom
 */
class midcom_helper_serviceloader
{
    private $instances = array();

    /**
     * @param string $service Service identifier to get implementation for
     * @return string Name of the implementation class
     */
    private function get_implementation($service)
    {
        if (!isset($GLOBALS['midcom_config']["service_{$service}"]))
        {
            // TODO: Exception here
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Service interface {$service} could not be loaded: Not defined in system configuration");
            // This will exit
        }

        // Load the interface class
        if (!interface_exists($service))
        {
            // TODO: Exception here
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Service interface {$service} could not be loaded: File not found");
            // This will exit
        }

        return $GLOBALS['midcom_config']["service_{$service}"];
    }

    /**
     * @param string $service Service identifier to check loadability
     * @return boolean Whether the service can be loaded
     */
    public function can_load($service)
    {
        // Start by checking what implementation is to be used
        $implementation_class = $this->get_implementation($service);

        if (   is_null($implementation_class)
            || empty($implementation_class))
        {
            // Service disabled for this site
            return false;
        }

        // Load the interface class
        if (!class_exists($implementation_class))
        {
            // TODO: Exception here
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Service implementation {$implementation_class} for {$service} could not be loaded: File not found");
            // This will exit
        }

        if (!array_key_exists($service, class_implements($implementation_class)))
        {
            // TODO: Exception here
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Class {$implementation_class} does not implement {$service}");
            // This will exit
        }
        // TODO: Also run the check method of the class itself

        return true;
    }

    /**
     * Instantiate and return the service object
     */
    public function load($service)
    {
        if (isset($this->instances[$service]))
        {
            return $this->instances[$service];
        }
        $implementation_class = $this->get_implementation($service);
        if (   is_null($implementation_class)
            || empty($implementation_class))
        {
            // Service disabled for this site
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Service implementation for {$service} not defined");
        }

        if (!$this->can_load($service))
        {
            // Service disabled for this site
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Service implementation for {$service} could not be loaded");
        }

        $this->instances[$service] = new $implementation_class();
        return $this->instances[$service];
    }
}
?>