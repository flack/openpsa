<?php
/**
 * OpenPSA notifications manager
 *
 * Startup loads main class, which is used for all operations.
 *
 * @package org.openpsa.notifications
 * @author Henri Bergius, http://bergie.iki.fi
 * @version $Id: interfaces.php 22916 2009-07-15 09:53:28Z flack $
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.notifications
 */
class org_openpsa_notifications_interface extends midcom_baseclasses_components_interface
{
    /**
     * Initializes the library and loads needed files
     */
    function __construct()
    {
        parent::__construct();

        $this->_component = 'org.openpsa.notifications';
    }
}
?>