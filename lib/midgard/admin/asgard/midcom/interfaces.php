<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the interface class for midgard.admin.asgard
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        parent::__construct();
        $this->_component = 'midgard.admin.asgard';
        $this->_autoload_files = array
        (
            'plugin.php',
            'tree.php',
        );

        $this->_autoload_libraries = array
        (
            'midcom.helper.reflector',
            'midcom.admin.help',
        );

    }

    function _on_initialize()
    {
        // Enable jQuery
        $_MIDCOM->enable_jquery();

        return true;
    }

}
?>