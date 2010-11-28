<?php
/**
 * @package org.routamc.positioning
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: importer.php 23006 2009-07-24 08:29:00Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Position importing factory class. All importers inherit from this.
 *
 * @package org.routamc.positioning
 */
class org_routamc_positioning_importer extends midcom_baseclasses_components_purecode
{
    /**
     * The imported log entries
     *
     * @var org_routamc_positioning_log
     */
    var $log = null;

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
     * Initializes the class. The real startup is done by the initialize() call.
     */
    function __construct()
    {
         $this->_component = 'org.routamc.positioning';
         parent::__construct();
    }

    /**
     * Normalize coordinates into decimal values
     *
     * @return Array
     */
    function normalize_coordinates($latitude, $longitude)
    {
        $normalized_coordinates = Array
        (
            'latitude' => null,
            'longitude' => null,
        );

        if (!is_float($latitude))
        {
            // TODO: Convert to decimal
        }
        $normalized_coordinates['latitude'] = $latitude;

        if (!is_float($longitude))
        {
            // TODO: Convert to decimal
        }
        $normalized_coordinates['longitude'] = $longitude;

        return $normalized_coordinates;
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
     * Empty default implementation, this calls won't do much.
     *
     * @param Array $logs Log entries in Array format specific to importer
     * @return boolean Indicating success.
     */
    function import($logs)
    {
        return true;
    }

    /**
     * This is a static factory method which lets you dynamically create importer instances.
     * It takes care of loading the required class files. The returned instances will be created
     * but not initialized.
     *
     * @param string $type The type of the importer (the file name from the importer directory).
     * @return org_routamc_positioning_importer A reference to the newly created importer instance.
     */
    static function & create($type)
    {
        $filename = MIDCOM_ROOT . "/org/routamc/positioning/importer/{$type}.php";
        $classname = "org_routamc_positioning_importer_{$type}";
        require_once($filename);
        /**
         * Php 4.4.1 does not allow you to return a reference to an expression.
         * http://www.php.net/release_4_4_0.php
         */
        $class = new $classname();
        return $class;
    }
}
