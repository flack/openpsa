<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Direct marketing page handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The message which has been created
     *
     * @var org_openpsa_directmarketing_campaign_message_dba
     */
    private $_message = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new message.
     *
     * @var string
     */
    private $_schema = null;

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
        if (!array_key_exists($this->_schema, $this->_schemadb)) {
            throw new midcom_error_notfound('The type ' . $this->_schema . ' isn\'t available in the schemadb');
        }
        return $this->_schemadb;
    }

    public function get_schema_name()
    {
        return $this->_schema;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba();
        $this->_message->campaign = $this->_request_data['campaign']->id;
        $this->_message->orgOpenpsaObtype = $this->_schemadb[$this->_schema]->customdata['org_openpsa_directmarketing_messagetype'];

        if (!$this->_message->create()) {
            debug_print_r('We operated on this object:', $this->_message);
            throw new midcom_error('Failed to create a new message. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        return $this->_message;
    }

    /**
     * Displays an message create view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $data['campaign'] = $this->_master->load_campaign($args[0]);
        $data['campaign']->require_do('midgard:create');

        $this->_schema = $args[1];
        $data['controller'] = $this->get_controller('create');
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)));

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $data['controller'],
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        return "message/{$this->_message->guid}/";
    }
}
