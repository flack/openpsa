<?php
/**
 * @package org.openpsa.core
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @ignore
 */
//These two constants are on purpose in here
define('ORG_OPENPSA_CORE_VERSION_NUMBER', $_MIDCOM->componentloader->get_component_version('org.openpsa.core'));
define('ORG_OPENPSA_CORE_VERSION_NAME', 'Off the Grid');

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
    /**
     * Returns version number
     *
     * @return string OpenPSA version string
     */
    function get_version_number()
    {
            return ORG_OPENPSA_CORE_VERSION_NUMBER;
    }

    /**
     * Returns version name
     *
     * @return string OpenPSA version string
     */
    function get_version_name()
    {
            return ORG_OPENPSA_CORE_VERSION_NAME;
    }

    /**
     * Returns version number and name
     *
     * @return string OpenPSA version string
     */
    function get_version_both()
    {
      return ORG_OPENPSA_CORE_VERSION_NUMBER . ' (' . ORG_OPENPSA_CORE_VERSION_NAME . ')';
    }
}
?>