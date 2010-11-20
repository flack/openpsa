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
class midcom_helper_imagepopup_viewer extends midcom_baseclasses_components_request
{

    function __construct()
    {
        parent::__construct();
    }

    function get_plugin_handlers()
    {
        $_MIDCOM->load_library('midcom.helper.imagepopup');

        // Dumb $this on PHP5 workaround
        $object =& $this;

        // Match /folder/<schema>/<object guid>
        $object->_request_switch['list_folder'] = Array (
            'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
            'fixed_args' => Array('folder'),
            'variable_args' => 2,
        );

        // Match /folder/<schema>
        $object->_request_switch['list_folder_noobject'] = Array (
            'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
            'fixed_args' => Array('folder'),
            'variable_args' => 1,
        );

       // Match /unified/<schema>/<object guid>
       $object->_request_switch['list_unified'] = Array (
           'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
           'fixed_args' => Array('unified'),
           'variable_args' => 2,
       );

       // Match /unified/<schema>
       $object->_request_switch['list_unified_noobject'] = Array (
           'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
           'fixed_args' => Array('unified'),
           'variable_args' => 1,
       );

        // Match /<schema>/<object guid>
        $object->_request_switch['list_object'] = Array (
            'handler' => Array('midcom_helper_imagepopup_handler_list', 'list'),
            'variable_args' => 2,
        );

        return $object->_request_switch;
    }
}
?>