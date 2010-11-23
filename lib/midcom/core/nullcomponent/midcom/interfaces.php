<?php
/**
 * @package midcom.core.nullcomponent 
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the interface class for midcom.core.nullcomponent
 * 
 * @package midcom.core.nullcomponent
 */
class midcom_core_nullcomponent_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all script files and the datamanager library.
     */
    function __construct()
    {
        $this->_component = 'midcom.core.nullcomponent';

        // Load all mandatory class files of the component here
        $this->_autoload_files = array();
        
        // Load all libraries used by component here
        $this->_autoload_libraries = array
        (
        );
    }

}
?>