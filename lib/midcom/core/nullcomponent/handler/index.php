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
 * @package midcom.core.nullcomponent
 */
class midcom_core_nullcomponent_handler_index extends midcom_baseclasses_components_handler
{
    /**
     * The handler for the index article.
     */
    public function _handler_index(array &$data)
    {
        midcom::get()->style->prepend_component_styledir($this->_component);
        midcom::get()->head->set_pagetitle($this->_topic->extra);

        midcom::get()->metadata->set_request_metadata($this->_topic->metadata->revised, $this->_topic->guid);
        $data['node'] = $this->_topic;

        if ($this->_topic->id == midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC)->id
            && !midcom::get()->config->get('midcom_root_component')) {
            // if we have an ad hoc root topic, give user some options to do the initial setup
            return new midcom_response_relocate($this->router->generate('wizard'));
        }
        return $this->show('index');
    }
}
