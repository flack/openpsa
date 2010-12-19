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
     * @var org_openpsa_directmarketing_campaign_message
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
     *
     * Special treatement is done for the name field, which is set readonly for non-creates
     * if the simple_name_handling config option is set. (using an auto-generated urlname based
     * on the title, if it is missing.)
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
        return $this->_schemadb;
    }

    public function get_schema_name()
    {
    	return $this->_schema;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback (&$controller)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba();
        //  duh ? (copy-paste artefact ??)
        $this->_message->campaign = $this->_request_data['campaign']->id;
        $this->_message->orgOpenpsaObtype = $this->_schemadb[$this->_schema]->customdata['org_openpsa_directmarketing_messagetype'];

        if (! $this->_message->create())
        {
            debug_print_r('We operated on this object:', $this->_message);
            throw new midcom_error('Failed to create a new message. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        return $this->_message;
    }

    /**
     * Displays an message edit view.
     *
     * Note, that the message for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation message,
     */
    public function _handler_create($handler_id, $args, &$data)
    {
        $data['campaign'] = new org_openpsa_directmarketing_campaign_dba($args[0]);
        if ($data['campaign']->node != $this->_topic->id)
        {
            throw new midcom_error_notfound("The campaign {$args[0]} was not found.");
        }

        $_MIDCOM->auth->require_do('midgard:create', $data['campaign']);

        $this->set_active_leaf('campaign_' . $data['campaign']->id);

        $this->_schema = $args[1];
        $this->_load_schemadb();

        if (!array_key_exists($this->_schema, $this->_schemadb))
        {
            // This message type isn't available for our schema, return error
            return false;
        }

        $data['controller'] = $this->get_controller('create');

        switch ($data['controller']->process_form())
        {
            case 'save':
                // Index the message
                //$indexer = $_MIDCOM->get_service('indexer');
                //org_openpsa_directmarketing_viewer::index($data['controller']->datamanager, $indexer, $this->_topic);

                $_MIDCOM->relocate("message/{$this->_message->guid}/");

            case 'cancel':
                $_MIDCOM->relocate("campaign/{$data['campaign']->guid}/");
                // This will exit.
        }

        if ($this->_message != null)
        {
            $_MIDCOM->set_26_request_metadata($this->_message->metadata->revised, $this->_message->guid);
        }
        $data['view_title'] = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description));
        $_MIDCOM->set_pagetitle($data['view_title']);
        $this->add_breadcrumb("create/{$this->_schema}/", sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)));

        org_openpsa_helpers::dm2_savecancel($this);

        return true;
    }

    /**
     * Shows the loaded message.
     */
    public function _show_create ($handler_id, &$data)
    {
        midcom_show_style('show-message-new');
    }
}
?>