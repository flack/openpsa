<?php
/**
 * @package midcom.admin.babel
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.admin.babel
 */
class midcom_admin_babel_interface extends midcom_baseclasses_components_interface
{
    function __construct()
    {
        parent::__construct();

        $this->_component = 'midcom.admin.settings';
    }

}

?>