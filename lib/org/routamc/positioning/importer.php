<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Position importing factory class. All importers inherit from this.
 *
 * @package org.routamc.positioning
 */
abstract class org_routamc_positioning_importer extends midcom_baseclasses_components_purecode
{
    /**
     * The imported log entries
     *
     * @var org_routamc_positioning_log_dba
     */
    var $log;

    /**
     * Error code from trying to import. Either a midcom_connection::get_error_string() or an additional error code from component
     *
     * @var string
     */
    var $error = 'MGD_ERR_OK';

    /**
     * Error string from trying to import. Either a midcom_connection::get_error_string() or an additional error code from component
     *
     * @var string
     */
    var $error_string = '';

    /**
     * Normalize coordinates into decimal values
     *
     * @return Array
     */
    function normalize_coordinates($latitude, $longitude)
    {
        return array
        (
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
        );
    }

    /**
     * Map locations that are not yet mapped to their nearest city
     */
    function map_to_city($log)
    {
        // TODO: Find latest city
        return null;
    }

    /**
     * Run the actual import
     *
     * @param array $logs Log entries in Array format specific to importer
     * @param integer $person_id ID of the person to import logs for
     * @return boolean Indicating success.
     */
    abstract function import(array $logs, $person_id);

    /**
     * Dynamically create importer instances.
     * The returned instances will be created, but not initialized.
     *
     * @param string $type The type of the importer (the file name from the importer directory).
     * @return org_routamc_positioning_importer A reference to the newly created importer instance.
     */
    static function & create($type)
    {
        $classname = "org_routamc_positioning_importer_{$type}";
        if (!class_exists($classname))
        {
            throw new midcom_error("Requested importer class {$type} is not installed.");
        }

        $class = new $classname();
        return $class;
    }
}
