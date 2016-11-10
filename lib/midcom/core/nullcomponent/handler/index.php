<?php
/**
 * @package midcom.core.nullcomponent
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is a URL handler class for midcom.core.nullcomponent
 *
 * The midcom_baseclasses_components_handler class defines a bunch of helper vars
 *
 * @see midcom_baseclasses_components_handler
 * @package midcom.core.nullcomponent
 */
class midcom_core_nullcomponent_handler_index  extends midcom_baseclasses_components_handler
{
    /**
     * The handler for the index article.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param array &$data The local request data.
     */
    public function _handler_index($handler_id, array $args, array &$data)
    {
        midcom::get()->style->prepend_component_styledir($this->_component);
        midcom::get()->head->set_pagetitle($this->_topic->extra);

        midcom::get()->metadata->set_request_metadata($this->_topic->metadata->revised, $this->_topic->guid);
    }

    /**
     * This function does the output.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_index($handler_id, array &$data)
    {
        $data['node'] = $this->_topic;
        midcom_show_style('index');
    }
}
