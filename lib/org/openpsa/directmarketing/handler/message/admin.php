<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.net
 * @copyright The Midgard Project, http://www.midgard-project.net
 * @license http://www.gnu.net/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * directmarketing edit/delete message handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_admin extends midcom_baseclasses_components_handler
{
    /**
     * The message to operate on
     *
     * @var org_openpsa_directmarketing_campaign_message
     */
    private $_message;

    /**
     * The Controller of the message used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     */
    private $_schemadb;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['message'] = $this->_message;
        $this->_request_data['controller'] = $this->_controller;

        if ($this->_message->can_do('midgard:delete'))
        {
            $helper = new org_openpsa_widgets_toolbar($this->_view_toolbar);
            $helper->add_delete_button("message/delete/{$this->_message->guid}/", $this->_message->title);
        }
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
    }

    /**
     * Internal helper, loads the controller for the current message. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_message);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for message {$this->_message->id}.");
        }
    }

    /**
     * Helper, updates the context so that we get a complete breadcrum line towards the current
     * location.
     *
     * @param string $handler_id
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $this->add_breadcrumb("message/{$this->_message->guid}/", $this->_message->title);

        if ($handler_id == 'message_edit')
        {
            $this->add_breadcrumb("message/edit/{$this->_message->guid}/", $this->_l10n->get('edit message'));
        }
        else if ($handler_id == 'message_copy')
        {
            $this->add_breadcrumb("message/copy/{$this->_message->guid}/", $this->_l10n->get('copy message'));
        }
    }

    /**
     * Displays an message edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->_message->require_do('midgard:update');

        $data['campaign'] = new org_openpsa_directmarketing_campaign_dba($this->_message->campaign);
        $this->set_active_leaf('campaign_' . $data['campaign']->id);

        $this->_load_controller();
        $data['message_dm'] = $this->_controller;

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the message
                //$indexer = midcom::get()->indexer;
                //org_openpsa_directmarketing_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                return new midcom_response_relocate("message/{$this->_message->guid}/");
        }

        org_openpsa_helpers::dm2_savecancel($this);

        $this->_prepare_request_data();
        midcom::get()->head->set_pagetitle($this->_message->title);
        $this->bind_view_to_object($this->_message, $this->_request_data['controller']->datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Shows the loaded message.
     */
    public function _show_edit ($handler_id, array &$data)
    {
        midcom_show_style('show-message-edit');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->_message->require_do('midgard:delete');

        $controller = midcom_helper_datamanager2_handler::get_delete_controller();
        if ($controller->process_form() == 'delete')
        {
            if (!$this->_message->delete())
            {
                throw new midcom_error("Failed to delete message {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            $indexer = midcom::get()->indexer;
            $indexer->delete($this->_message->guid);
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $this->_message->title));
            $campaign = new org_openpsa_directmarketing_campaign_dba($this->_message->campaign);
            return new midcom_response_relocate("campaign/{$campaign->guid}/");
        }
        return new midcom_response_relocate("message/{$this->_message->guid}/");
    }

    /**
     * Handle the message copying interface
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_copy($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:create');

        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $guid = $args[0];

        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message_copy'));
        $this->_controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->initialize();

        $data['targets'] = array();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $copy = new midcom_helper_reflector_copy();
                $campaigns = $this->_controller->datamanager->types['campaign']->convert_to_storage();
                $qb = org_openpsa_directmarketing_campaign_dba::new_query_builder();
                $qb->add_constraint('guid', 'IN', $campaigns);
                $campaigns = $qb->execute();
                $original = $this->_message;
                $copy_objects = array();

                foreach ($campaigns as $campaign)
                {
                    $new_object = $copy->copy_object($original, $campaign);
                    $guid = $new_object->guid;

                    // Store for later use
                    $copy_objects[] = $new_object;
                }

                if (count($copy_objects) > 1)
                {
                    $data['targets'] = $copy_objects;
                    break;
                }
                // Fall through

            case 'cancel':
                return new midcom_response_relocate("message/{$guid}/");
        }

        midcom::get()->head->set_pagetitle($this->_message->title);
        $this->bind_view_to_object($this->_message);
        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Show the copy interface
     */
    public function _show_copy($handler_id, array &$data)
    {
        $data['controller'] = $this->_controller;

        if (count($data['targets']) > 0)
        {
            midcom_show_style('show-message-copy-ok');
            return;
        }

        midcom_show_style('show-message-copy');
    }
}
?>