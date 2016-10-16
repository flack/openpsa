<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Position geocoding factory class. All geocoders inherit from this.
 *
 * @package org.routamc.positioning
 */
abstract class org_routamc_positioning_geocoder extends midcom_baseclasses_components_purecode
{
    /**
     * Error code from trying to geocode. Either a midcom_connection::get_error_string() or an additional error code from component
     *
     * @var string
     */
    public $error = 'MGD_ERR_OK';

    /**
     * Geocode information
     *
     * @param array $location Parameters to geocode with, conforms to XEP-0080
     * @param array $options Implementation-specific configuration
     * @return array containing geocoded information
     */
    abstract public function geocode(array $location, array $options = array());

    /**
     * This is a static factory method which lets you dynamically create geocoder instances.
     * It takes care of loading the required class files. The returned instances will be created
     * but not initialized.
     *
     * @param string $type The type of the geocoder (the file name from the geocoder directory).
     * @return org_routamc_positioning_geocoder A reference to the newly created geocoder instance.
     */
    public static function & create($type)
    {
        $classname = "org_routamc_positioning_geocoder_{$type}";
        if (!class_exists($classname))
        {
            throw new midcom_error("Geocoder {$type} not available.");
        }

        $class = new $classname();
        return $class;
    }
}
