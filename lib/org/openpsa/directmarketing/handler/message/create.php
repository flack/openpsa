<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: create.php 25716 2010-04-20 22:57:24Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Direct marketing page handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_create extends midcom_baseclasses_components_handler
{
    /**
     * The message which has been created
     *
     * @var org_openpsa_directmarketing_campaign_message
     * @access private
     */
    var $_message = null;

    /**
     * The Controller of the message used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     * @access private
     */
    var $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     * @access private
     */
    var $_schemadb = null;

    /**
     * The schema to use for the new message.
     *
     * @var string
     * @access private
     */
    var $_schema = null;

    /**
     * The defaults to use for the new message.
     *
     * @var array
     * @access private
     */
    var $_defaults = array();

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $this->_request_data['controller'] =& $this->_controller;
        $this->_request_data['schema'] =& $this->_schema;
    }

    /**
     * Loads and prepares the schema database.
     *
     * Special treatement is done for the name field, which is set readonly for non-creates
     * if the simple_name_handling config option is set. (using an auto-generated urlname based
     * on the title, if it is missing.)
     *
     * The operations are done on all available schemas within the DB.
     */
    function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     *
     * @access private
     */
    function _load_controller()
    {
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->defaults = $this->_defaults;
        $this->_controller->callback_object =& $this;
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 create controller.");
            // This will exit.
        }
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    function & dm2_create_callback (&$controller)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba();
        //  duh ? (copy-paste artefact ??)
        $this->_message->campaign = $this->_request_data['campaign']->id;
        $this->_message->orgOpenpsaObtype = $this->_schemadb[$this->_schema]->customdata['org_openpsa_directmarketing_messagetype'];

        if (! $this->_message->create())
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('We operated on this object:', $this->_message);
            debug_pop();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Failed to create a new message, cannot continue. Last Midgard error was: ' . midcom_connection::get_error_string());
            // This will exit.
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
    function _handler_create($handler_id, $args, &$data)
    {
        $data['campaign'] = new org_openpsa_directmarketing_campaign_dba($args[0]);
        if (   !$data['campaign']
            || $data['campaign']->node != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The campaign {$args[0]} was not found.");
            // This will exit.
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

        $this->_load_controller();
        $this->_prepare_request_data();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Index the message
                //$indexer = $_MIDCOM->get_service('indexer');
                //org_openpsa_directmarketing_viewer::index($this->_controller->datamanager, $indexer, $this->_topic);

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
        $this->_update_breadcrumb_line($handler_id);

        org_openpsa_helpers::dm2_savecancel($this);

        return true;
    }

    /**
     * Helper, updates the context so that we get a complete breadcrum line towards the current
     * location.
     *
     */
    private function _update_breadcrumb_line()
    {
        $tmp = array();

        $tmp[] = array
        (
            MIDCOM_NAV_URL => "create/{$this->_schema}/",
            MIDCOM_NAV_NAME => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)),
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    /**
     * Shows the loaded message.
     */
    function _show_create ($handler_id, &$data)
    {
        midcom_show_style('show-message-new');
    }
}

?>