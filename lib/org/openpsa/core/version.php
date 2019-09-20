<?php
/**
 * @package org.openpsa.core
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Returns current version of OpenPSA. Three different modes are supported:
 *  version number (version name)
 *  version number
 *  version name
 *
 * @package org.openpsa.core
 */
class org_openpsa_core_version
{
    const NAME = 'Off the Grid';

    /**
     * Returns version number
     */
    public static function get_version_number() : string
    {
        return midcom::VERSION;
    }

    /**
     * Returns version name
     */
    public static function get_version_name() : string
    {
        return self::NAME;
    }

    /**
     * Returns version number and name
     */
    public static function get_version_both() : string
    {
        return self::get_version_number() . ' (' . self::get_version_name() . ')';
    }
}
