<?php
/**
 * @package midcom.baseclasses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Base class for plugins
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_plugin extends midcom_baseclasses_components_handler
{
    public function get_plugin_handlers()
    {
        $handlers = midcom_baseclasses_components_configuration::get($this->_component, 'routes');
        return $handlers;
    }

    public function initialize(&$data)
    {
        $this->_on_initialize($data);
    }

    public function _on_initialize(&$data){}
}
?>
