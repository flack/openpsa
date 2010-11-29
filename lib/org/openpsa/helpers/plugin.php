<?php
/**
 * @package org.openpsa.helpers
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for various global OpenPSA functions
 *
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers_plugin extends midcom_baseclasses_components_purecode
{
    public function __construct()
    {
        $this->_component = 'org.openpsa.helpers';
        parent::__construct();
    }

    static function get_plugin_handlers()
    {
        $switch = array();

        // Match /chooser/create/<type>/
        $switch['render_sort'] = array
        (
            'handler' => array('org_openpsa_helpers_handler_chooser', 'create'),
            'fixed_args' => array('chooser', 'create'),
            'variable_args' => 1,
        );

        return $switch;
    }
}
?>