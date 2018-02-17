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
 * interface class
 *
 * @package midcom
 */
class midcom_helper_serviceloader
{
    private $instances = [];

    /**
     * @param string $service Service identifier to get implementation for
     * @return string Name of the implementation class
     */
    private function get_implementation($service)
    {
        if (midcom::get()->config->get("service_{$service}") === null) {
            throw new midcom_error("Service interface {$service} could not be loaded: Not defined in system configuration");
        }

        // Load the interface class
        if (!interface_exists($service)) {
            throw new midcom_error("Service interface {$service} could not be loaded: File not found");
        }

        return midcom::get()->config->get("service_{$service}");
    }

    /**
     * @param string $service Service identifier to check loadability
     * @return boolean Whether the service can be loaded
     */
    public function can_load($service)
    {
        // Start by checking what implementation is to be used
        $implementation_class = $this->get_implementation($service);

        if (empty($implementation_class)) {
            // Service disabled for this site
            return false;
        }

        // Load the interface class
        if (!class_exists($implementation_class)) {
            throw new midcom_error("Service implementation {$implementation_class} for {$service} could not be loaded: File not found");
        }

        if (!array_key_exists($service, class_implements($implementation_class))) {
            throw new midcom_error("Class {$implementation_class} does not implement {$service}");
        }
        // TODO: Also run the check method of the class itself

        return true;
    }

    /**
     * Instantiate and return the service object
     */
    public function load($service)
    {
        if (!isset($this->instances[$service])) {
            if (!$this->can_load($service)) {
                // Service disabled for this site
                throw new midcom_error("Service implementation for {$service} could not be loaded");
            }
            $implementation_class = $this->get_implementation($service);
            $this->instances[$service] = new $implementation_class();
        }
        return $this->instances[$service];
    }
}
