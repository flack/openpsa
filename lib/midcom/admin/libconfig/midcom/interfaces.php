<?php
/**
 * @package midcom.admin.libconfig
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: interfaces.php 23025 2009-07-28 10:03:50Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Library configuration Interface Class. This is a pure code library.
 *
 * @package midcom.admin.libconfig
 */
class midcom_admin_libconfig_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing needs to be done, besides connecting to the parent class constructor.
     */
    function __construct()
    {
        parent::__construct();
        $this->_component = 'midcom.admin.libconfig';
    }
}
?>