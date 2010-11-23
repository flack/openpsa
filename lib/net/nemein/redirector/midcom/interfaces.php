<?php
/**
 * @package net.nemein.redirector
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interfaces.php 21500 2009-03-26 09:54:24Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Redirector MidCOM interface class.
 *
 * @package net.nemein.redirector
 */
class net_nemein_redirector_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        $this->_component = 'net.nemein.redirector';
    }
}
?>