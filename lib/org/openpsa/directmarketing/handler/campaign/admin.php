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
 * Originally copied from net.nehmer.blog
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
     * The Datamanager of the campaign to display (for delete mode)
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

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
    private function _prepare_request_data($handler_id)
    {
        $this->_request_data['campaign'] =& $this->_campaign;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;
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
     * Internal helper, loads the datamanager for the current campaign. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        if (!$this->_datamanager->autoset_storage($this->_campaign))
        {
            throw new midcom_error("Failed to create a DM2 instance for campaign {$this->_campaign->id}.");
        }
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
        switch ($handler_id)
        {
            case 'edit_campaign':
                $this->add_breadcrumb("campaign/edit/{$this->_campaign->guid}/", $this->_l10n->get('edit campaign'));
                break;
            case 'delete_campaign':
                $this->add_breadcrumb("campaign/delete/{$this->_campaign->guid}/", $this->_l10n->get('delete campaign'));
                break;
            case 'edit_campaign_query':
                $this->add_breadcrumb("campaign/edit_query/{$this->_campaign->guid}/", $this->_l10n->get('edit rules'));
                break;
            case 'edit_campaign_query_advanced':
                $this->add_breadcrumb("campaign/edit_query/{$this->_campaign->guid}/", $this->_l10n->get('edit rules'));
                $this->add_breadcrumb("campaign/edit_query_advanced/{$this->_campaign->guid}/", $this->_l10n->get('advanced rule editor'));
                break;
        }
    }

    /**
     * Displays an campaign edit view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    public function _handler_edit_query($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);
        $this->_campaign->require_do('midgard:update');

        $this->_prepare_request_data($handler_id);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/edit_query_advanced/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('advanced rule editor'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
            )
        );
        midcom::get('head')->enable_jquery();
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.css');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/list.css');

        // PONDER: Locking ?

        if (!empty($_POST['midcom_helper_datamanager2_cancel']))
        {
            return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
        }

        //check if it should be saved or preview
        if (   !empty($_POST['midcom_helper_datamanager2_save'])
            || isset($_POST['show_rule_preview']))
        {
            $eval = '$tmp_array = ' . $_POST['midcom_helper_datamanager2_dummy_field_rules'] . ';';
            $eval_ret = eval($eval);

            if (   $eval_ret === false
                || !is_array($tmp_array))
            {
                //Rule could not be parsed
                midcom::get('uimessages')->add('org.openpsa.directmarketing', $this->_l10n->get('given rule could not be parsed'), 'error');
                return;
            }
            if (count($tmp_array) == 0)
            {
                // Rule array is empty
                midcom::get('uimessages')->add('org.openpsa.directmarketing', $this->_l10n->get('given rule is empty'), 'error');
                return;
            }
            $rule = $tmp_array;
            //add rule was generated with wizard
            $rule['generated'] = 'wizard';

            //resolve rules
            $solver = new org_openpsa_directmarketing_campaign_ruleresolver();
            $solver->resolve($tmp_array);
            $rule_persons = $solver->execute();


            //if it's not preview update campaign & Schedule background members refresh'
            if (!isset($_POST['show_rule_preview']))
            {
                $this->_campaign->rules = $rule;
                $update_ret = $this->_campaign->update();
                if (!$update_ret)
                {
                    //Save failed
                    midcom::get('uimessages')->add('org.openpsa.directmarketing', sprintf($this->_l10n->get('error when saving rule, errstr: %s'), midcom_connection::get_error_string()), 'error');
                    break;
                }
                //Schedule background members refresh
                $this->_campaign->schedule_update_smart_campaign_members();

                //Save ok, relocate
                return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
            }
            //set data for preview & skip page_style because of javascript call
            $data['preview_persons'] = $rule_persons;
            midcom::get()->skip_page_style = true;
        }

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        midcom::get('head')->set_pagetitle($this->_campaign->title);
        $this->bind_view_to_object($this->_campaign);
        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Shows the loaded campaign.
     */
    public function _show_edit_query($handler_id, array &$data)
    {
        if (isset($_POST['show_rule_preview']))
        {
            midcom_show_style('show-campaign-preview');
        }
        else
        {
            midcom_show_style('show-campaign-edit_query');
        }
    }

    /**
     * Displays an campaign edit view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    public function _handler_edit_query_advanced($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);
        $this->_campaign->require_do('midgard:update');

        $this->_prepare_request_data($handler_id);

        if (!empty($_POST['midcom_helper_datamanager2_cancel']))
        {
            return new midcom_response_relocate(midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX)
                . "campaign/" . $this->_request_data["campaign"]->guid . '/');
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.css');

        if (!empty($_POST['midcom_helper_datamanager2_save']))
        {
            //Actual save routine
            if (empty($_POST['midcom_helper_datamanager2_dummy_field_rules']))
            {
                //Rule code empty
                midcom::get('uimessages')->add('org.openpsa.directmarketing', $this->_l10n->get('no rule given'), 'error');
                return;
            }
            $eval = '$tmp_array = ' . $_POST['midcom_helper_datamanager2_dummy_field_rules'] . ';';
            $eval_ret = @eval($eval);
            if (   $eval_ret === false
                || !is_array($tmp_array))
            {
                //Rule could not be parsed
                midcom::get('uimessages')->add('org.openpsa.directmarketing', $this->_l10n->get('given rule could not be parsed'), 'error');
                return;
            }
            if (count($tmp_array) == 0)
            {
                // Rule array is empty
                midcom::get('uimessages')->add('org.openpsa.directmarketing', $this->_l10n->get('given rule is empty'), 'error');
                return;
            }
            if (array_key_exists('generated_from',  $tmp_array))
            {
                debug_add('"generated_from" found in advanced rule, removing', MIDCOM_LOG_WARN);
                unset ($tmp_array['generated_from']);
                // PONDER: return to editor or save anyway ? now we overwrite the value with the modified rule and return to editor.
                midcom::get('uimessages')->add('org.openpsa.directmarketing', $this->_l10n->get('longtext:generated_from_found_in_adv_rule'), 'error');
                $_POST['midcom_helper_datamanager2_dummy_field_rules'] = org_openpsa_helpers::array2code($tmp_array);

                $this->_update_breadcrumb_line($handler_id);
                org_openpsa_helpers::dm2_savecancel($this);
                return;
            }
            $this->_request_data['campaign']->rules = $tmp_array;
            $update_ret = $this->_request_data['campaign']->update();
            if (!$update_ret)
            {
                //Save failed
                midcom::get('uimessages')->add('org.openpsa.directmarketing', sprintf($this->_l10n->get('error when saving rule, errstr: %s'), midcom_connection::get_error_string()), 'error');
                return;
            }

            //Schedule background members refresh
            $this->_request_data['campaign']->schedule_update_smart_campaign_members();

            //Save ok, relocate
            return new midcom_response_relocate(midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX)
                . "campaign/" . $this->_request_data["campaign"]->guid . '/');
        }

        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        midcom::get('head')->set_pagetitle($this->_campaign->title);

        org_openpsa_helpers::dm2_savecancel($this);
        $this->bind_view_to_object($this->_campaign);

        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Shows the loaded campaign.
     */
    public function _show_edit_query_advanced($handler_id, array &$data)
    {
        midcom_show_style('show-campaign-edit_query-advanced');
    }

    /**
     * Displays an campaign edit view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);

        $this->_campaign->require_do('midgard:update');
        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the campaign
                //$indexer = midcom::get('indexer');
                //org_openpsa_directmarketing_viewer::index($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
        }

        org_openpsa_helpers::dm2_savecancel($this);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/delete/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete campaign'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:delete'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
            )
        );

        $this->_prepare_request_data($handler_id);
        midcom::get('head')->set_pagetitle($this->_campaign->title);
        $this->bind_view_to_object($this->_campaign, $this->_request_data['controller']->datamanager->schema->name);
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
     * Displays an campaign delete confirmation view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);
        $this->_campaign->require_do('midgard:delete');

        $this->_load_datamanager();

        if (array_key_exists('org_openpsa_directmarketing_deleteok', $_REQUEST))
        {
            // Deletion confirmed.
            if (! $this->_campaign->delete())
            {
                throw new midcom_error("Failed to delete campaign {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            // Update the index
            $indexer = midcom::get('indexer');
            $indexer->delete($this->_campaign->guid);

            // Delete ok, relocating to welcome.
            return new midcom_response_relocate('');
        }

        if (array_key_exists('org_openpsa_directmarketing_deletecancel', $_REQUEST))
        {
            // Redirect to view page.
            return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
        }

        $this->set_active_leaf('campaign_' .$this->_campaign->id);

        $this->_prepare_request_data($handler_id);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/edit/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit campaign'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

        midcom::get('head')->set_pagetitle($this->_campaign->title);
        $this->bind_view_to_object($this->_campaign, $this->_datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);
    }

    /**
     * Shows the loaded campaign.
     */
    public function _show_delete ($handler_id, array &$data)
    {
        $data['view_campaign'] = $this->_datamanager->get_content_html();

        midcom_show_style('show-campaign-delete');
    }
}
?>
