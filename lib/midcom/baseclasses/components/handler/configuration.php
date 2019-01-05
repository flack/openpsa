<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * midcom.datamanager based configuration
 *
 * Usage:
 *
 * 1. Write a datamanager schema compatible configuration
 *    schema and place it among your component files
 * 2. Point a configuration key 'schemadb_config' to it within your
 *    component configuration (_config/config.inc_)
 * 3. Refer to the component configuration helper with a request handler,
 *    e.g.
 *
 * <code>
 *     $this->_request_handler['config'] = array
 *     (
 *         'handler' => ['midcom_baseclasses_components_handler_configuration', 'config'],
 *         'fixed_args' => ['config'],
 *     );
 * </code>
 *
 * @package midcom.baseclasses
 */
class midcom_baseclasses_components_handler_configuration extends midcom_baseclasses_components_handler
{
    public function __construct()
    {
        $this->_component = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
        parent::__construct();
    }

    private function load_controller()
    {
        if ($schemadb_path = $this->_config->get('schemadb_config')) {
            return datamanager::from_schemadb($schemadb_path)
                ->set_storage($this->_topic, 'config')
                ->get_controller();
        }
        throw new midcom_error("No configuration schema defined");
    }

    /**
     * Generic handler for all the DM2 based configuration requests
     */
    public function _handler_config()
    {
        // Require corresponding ACLs
        $this->_topic->require_do('midgard:update');
        $this->_topic->require_do('midcom:component_config');

        midcom::get()->head->set_pagetitle($this->_l10n_midcom->get('component configuration'));

        $workflow = $this->get_workflow('datamanager', ['controller' => $this->load_controller()]);
        if ($this instanceof midcom_baseclasses_components_handler_configuration_recreate) {
            $workflow->add_post_button('config/recreate/', $this->_l10n_midcom->get('recreate images'), [
                'midcom_baseclasses_components_handler_configuration_recreateok' => true,
            ]);
        }
        $response = $workflow->run();

        if ($workflow->get_state() == 'save') {
            midcom::get()->uimessages->add($this->_l10n_midcom->get('component configuration'), $this->_l10n_midcom->get('configuration saved'));
        }
        return $response;
    }
}
