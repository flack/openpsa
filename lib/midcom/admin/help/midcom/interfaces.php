<?php
/**
 * @package midcom.admin.help
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interfaces.php 22531 2009-06-16 09:17:48Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM online help interface class
 *
 * @package midcom.admin.help
 */
class midcom_admin_help_interface extends midcom_baseclasses_components_interface 
{
    function __construct() 
    {
        $this->_autoload_files = array
        (
            'help.php',
        );
        $this->_autoload_libraries = array
        (
            'net.nehmer.markdown',
        );
    }
}

?>