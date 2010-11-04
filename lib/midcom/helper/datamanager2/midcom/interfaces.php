<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interfaces.php 17556 2008-09-16 20:14:11Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Component Interface Class. This is a pure code library.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_interface extends midcom_baseclasses_components_interface
{
    /**
     * Constructor.
     *
     * Nothing fancy, loads all core script files
     */
    function __construct()
    {
        parent::__construct();

        $this->_component = 'midcom.helper.datamanager2';

        // Subclasses are loaded on demand, add this to the above list for syntax checking:
        /*
            'type/text.php',
        */
    }


}

?>