<?php
/**
 * @author tarjei huse
 * @package midcom.helper.imagepopup
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the class that defines which URLs should be handled by this module.
 *
 * @package midcom.helper.imagepopup
 */
class midcom_helper_imagepopup_viewer extends midcom_baseclasses_components_plugin
{
    function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.imagepopup');
    }
}
?>