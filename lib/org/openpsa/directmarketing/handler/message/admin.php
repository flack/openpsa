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
 * Originally copied from net.nehmer.blog
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
    private $_message = null;

    /**
     * The Datamanager of the message to display (for delete mode)
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

    /**
     * The Controller of the message used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     */
    private $_schemadb = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data($handler_id)
    {
        $this->_request_data['message'] =& $this->_message;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "message/edit/{$this->_message->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit message'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_message->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "message/delete/{$this->_message->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete message'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_message->can_do('midgard:delete'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            )
        );

        switch ($handler_id)
        {
            case 'message_edit':
                $this->_view_toolbar->disable_item("message/edit/{$this->_message->guid}/");
                break;
            case 'message_delete':
                $this->_view_toolbar->disable_item("message/delete/{$this->_message->guid}/");
                break;
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
     * Internal helper, loads the datamanager for the current message. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        if (!$this->_datamanager->autoset_storage($this->_message))
        {
            throw new midcom_error("Failed to create a DM2 instance for message {$this->_message->id}.");
        }
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

        switch ($handler_id)
        {
            case 'message_edit':
                $this->add_breadcrumb("message/edit/{$this->_message->guid}/", $this->_l10n->get('edit message'));
                break;
            case 'message_delete':
                $this->add_breadcrumb("message/delete/{$this->_message->guid}/", $this->_l10n->get('delete message'));
                break;
            case 'message_copy':
                $this->add_breadcrumb("message/copy/{$this->_message->guid}/", $this->_l10n->get('copy message'));
                break;
        }
    }

    /**
     * Displays an message edit view.
     *
     * Note, that the message for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation message,
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->_message->require_do('midgard:update');

        $data['campaign'] = new org_openpsa_directmarketing_campaign_dba($this->_message->campaign);
        $this->set_active_leaf('campaign_' . $data['campaign']->id);

        $this->_load_controller();
        $data['message_dm'] =& $this->_controller;

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the message
                //$indexer = midcom::get('indexer');
                //org_openpsa_directmarketing_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                midcom::get()->relocate("message/{$this->_message->guid}/");
                // This will exit.
        }

        org_openpsa_helpers::dm2_savecancel($this);

        $this->_prepare_request_data($handler_id);
        midcom::get('head')->set_pagetitle($this->_message->title);
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
     * Displays an message delete confirmation view.
     *
     * Note, that the message for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation message,
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $this->_message->require_do('midgard:delete');

        $data['campaign'] = new org_openpsa_directmarketing_campaign_dba($this->_message->campaign);
        $this->set_active_leaf('campaign_' . $data['campaign']->id);

        $this->_load_datamanager();

        if (array_key_exists('org_openpsa_directmarketing_deleteok', $_REQUEST))
        {
            // Deletion confirmed.
            if (!$this->_message->delete())
            {
                throw new midcom_error("Failed to delete message {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            // Update the index
            $indexer = midcom::get('indexer');
            $indexer->delete($this->_message->guid);

            // Delete ok, relocating to welcome.
            midcom::get()->relocate("campaign/{$data['campaign']->guid}/");
            // This will exit.
        }

        if (array_key_exists('org_openpsa_directmarketing_deletecancel', $_REQUEST))
        {
            // Redirect to view page.
            midcom::get()->relocate("message/{$this->_message->guid}/");
            // This will exit()
        }

        $this->_prepare_request_data($handler_id);
        midcom::get('head')->set_pagetitle($this->_message->title);
        $this->bind_view_to_object($this->_message, $this->_datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);
    }


    /**
     * Shows the loaded message.
     */
    public function _show_delete ($handler_id, array &$data)
    {
        $data['view_message'] = $this->_datamanager->get_content_html();

        midcom_show_style('show-message-delete');
    }

    /**
     * Handle the message copying interface
     *
     * @return boolean Indicating success
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
                midcom::get('componentloader')->load('midcom.helper.reflector');
                $copy = new midcom_helper_reflector_copy();
                $campaigns = $this->_controller->datamanager->types['campaign']->convert_to_storage();
                $copy_objects = array();

                foreach ($campaigns as $campaign_id)
                {
                    try
                    {
                        $campaign = new org_openpsa_directmarketing_campaign_dba($campaign_id);
                    }
                    catch (midcom_error $e)
                    {
                        continue;
                    }

                    $new_object = $copy->copy_object($this->_message->guid, $campaign);
                    $guid = $new_object->guid;

                    // Store for later use
                    $copy_objects[] = $new_object;
                }

                if (count($copy_objects) > 1)
                {
                    $data['targets'] =& $copy_objects;
                    break;
                }
                // Fall through

            case 'cancel':
                midcom::get()->relocate("message/{$guid}/");
                // This will exit
        }

        midcom::get('head')->set_pagetitle($this->_message->title);
        $this->bind_view_to_object($this->_message);
        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Show the copy interface
     */
    public function _show_copy($handler_id, array &$data)
    {
        $data['controller'] =& $this->_controller;

        if (count($data['targets']) > 0)
        {
            midcom_show_style('show-message-copy-ok');
            return;
        }

        midcom_show_style('show-message-copy');
    }
}
?>