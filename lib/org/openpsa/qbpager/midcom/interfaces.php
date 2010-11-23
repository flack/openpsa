<?php
/**
 * @package org.openpsa.qbpager
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA qbpager library, handles paging of QB resultsets.
 *
 * Startup loads main class, which is used for all operations.
 *
 * @package org.openpsa.qbpager
 */
class org_openpsa_qbpager_interface extends midcom_baseclasses_components_interface
{
    function __construct()
    {
        $this->_component = 'org.openpsa.qbpager';
        $this->_autoload_libraries = array
        (
            'midcom.helper.xsspreventer',
        );
    }
}

?>