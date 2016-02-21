<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.net
 * @copyright The Midgard Project, http://www.midgard-project.net
 * @license http://www.gnu.net/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * directmarketing edit/delete campaign handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_campaign_admin extends midcom_baseclasses_components_handler
{
    /**
     * The campaign to operate on
     *
     * @var org_openpsa_directmarketing_campaign
     */
    private $_campaign = null;

    /**
     * The Controller of the campaign used for editing
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
    private function _prepare_request_data()
    {
        $this->_request_data['campaign'] = $this->_campaign;
        $this->_request_data['controller'] = $this->_controller;
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign'));
    }

    /**
     * Internal helper, loads the controller for the current campaign. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_campaign);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for campaign {$this->_campaign->id}.");
        }
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $this->add_breadcrumb("campaign/edit/{$this->_campaign->guid}/", $this->_l10n->get('edit campaign'));
    }

    /**
     * Displays an campaign edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);
        $this->_campaign->require_do('midgard:update');

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the campaign
                //$indexer = midcom::get()->indexer;
                //org_openpsa_directmarketing_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
        }

        $this->_prepare_request_data();
        midcom::get()->head->set_pagetitle($this->_campaign->title);
        $this->bind_view_to_object($this->_campaign, $this->_controller->datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Shows the loaded campaign.
     */
    public function _show_edit ($handler_id, array &$data)
    {
        midcom_show_style('show-campaign-edit');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);
        $workflow = new midcom\workflow\delete($this->_campaign);
        if ($workflow->run())
        {
            return new midcom_response_relocate('');
        }

        return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
    }
}
