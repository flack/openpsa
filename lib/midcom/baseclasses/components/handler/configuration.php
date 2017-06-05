<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * midcom.helper.datamanager2 based configuration
 *
 * Usage:
 *
 * 1. Write a midcom_helper_datamanager2_schema compatible configuration
 *    schema and place it among your component files
 * 2. Point a configuration key 'schemadb_config' to it within your
 *    component configuration (_config/config.inc_)
 * 3. Refer to DM2 component configuration helper with a request handler,
 *    e.g.
 *
 * <code>
 *     $this->_request_handler['config'] = array
 *     (
 *         'handler' => array ('midcom_baseclasses_components_handler_configuration', 'config'),
 *         'fixed_args' => array ('config'),
 *     );
 * </code>
 *
 * @package midcom.baseclasses
 */
class midcom_baseclasses_components_handler_configuration extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * DM2 controller instance
     *
     * @var midcom_helper_datamanager2_controller $_controller
     */
    private $_controller;

    public function __construct()
    {
        $this->_component = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
        parent::__construct();
    }

    public function get_schema_name()
    {
        if (isset($this->_master->_handler['schema'])) {
            return $this->_master->_handler['schema'];
        }
        return 'config';
    }

    public function load_schemadb()
    {
        if (isset($this->_master->_handler['schemadb'])) {
            $schemadb_path = $this->_master->_handler['schemadb'];
        } elseif ($this->_config->get('schemadb_config')) {
            $schemadb_path = $this->_config->get('schemadb_config');
        } else {
            throw new midcom_error("No configuration schema defined");
        }

        $schemadb = midcom_helper_datamanager2_schema::load_database($schemadb_path);

        if (empty($schemadb)) {
            throw new midcom_error('Failed to load configuration schemadb');
        }

        return $schemadb;
    }

    /**
     * Generic handler for all the DM2 based configuration requests
     *
     * @param string $handler_id    Name of the handler
     * @param array  $args          Variable arguments
     * @param array  &$data          Miscellaneous output data
     */
    public function _handler_config($handler_id, array $args, array &$data)
    {
        // Require corresponding ACL's
        $this->_topic->require_do('midgard:update');
        $this->_topic->require_do('midcom:component_config');

        // Load the midcom_helper_datamanager2_controller for form processing
        $this->_controller = $this->get_controller('simple', $this->_topic);

        midcom::get()->head->set_pagetitle($this->_l10n_midcom->get('component configuration'));

        $workflow = $this->get_workflow('datamanager2', ['controller' => $this->_controller]);
        if (   method_exists($this, '_load_datamanagers')
            && method_exists($this, '_load_objects')) {
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

    /**
     * Handler for regenerating all derived images used in the folder.
     *
     * If used in a component, you should implement the _load_datamanagers and _load_objects methods in an
     * inherited handler class.
     *
     * _load_datamanagers must return an array of midcom_helper_datamanager2_datamanager objects indexed by
     * DBA class name.
     *
     * _load_objects must return an array of DBA objects.
     *
     * @param string $handler_id    Name of the handler
     * @param array  $args          Variable arguments
     * @param array  &$data          Miscellaneous output data
     */
    public function _handler_recreate($handler_id, array $args, array &$data)
    {
        if (!method_exists($this, '_load_datamanagers')) {
            throw new midcom_error_notfound('_load_datamanagers method not available, recreation support disabled.');
        }

        if (!method_exists($this, '_load_objects')) {
            throw new midcom_error_notfound('_load_objects method not available, recreation support disabled.');
        }

        // Require corresponding ACL's
        $this->_topic->require_do('midgard:update');
        $this->_topic->require_do('midcom:component_config');

        if (!array_key_exists('midcom_baseclasses_components_handler_configuration_recreateok', $_POST)) {
            return new midcom_response_relocate('config/');
        }

        $data['datamanagers'] = $this->_load_datamanagers();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('recreate images for folder %s'), $data['topic']->extra));
        $workflow = $this->get_workflow('viewer');
        return $workflow->run();
    }

    /**
     * Show the recreation screen
     *
     * @param string $handler_id    Name of the handler
     * @param array  $data          Miscellaneous output data
     */
    public function _show_recreate($handler_id, array &$data)
    {
        midcom::get()->disable_limits();

        midcom::get()->style->data['objects'] = $this->_load_objects();
        midcom::get()->style->data['datamanagers'] = $data['datamanagers'];

        midcom::get()->style->show_midcom('dm2_config_recreate');
    }
}
