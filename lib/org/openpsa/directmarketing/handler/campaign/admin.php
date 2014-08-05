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
        $this->_request_data['campaign'] = $this->_campaign;
        $this->_request_data['datamanager'] = $this->_datamanager;
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
        switch ($handler_id)
        {
            case 'edit_campaign':
                $this->add_breadcrumb("campaign/edit/{$this->_campaign->guid}/", $this->_l10n->get('edit campaign'));
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
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit_query($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);
        $this->_campaign->require_do('midgard:update');

        $this->_prepare_request_data($handler_id);

        // PONDER: Locking ?

        if (!empty($_POST['midcom_helper_datamanager2_cancel']))
        {
            return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
        }

        //check if it should be saved or preview
        if (   !empty($_POST['midcom_helper_datamanager2_save'])
            || isset($_POST['show_rule_preview']))
        {
            try
            {
                $rules = $this->_load_rules_from_post();
            }
            catch (midcom_error $e)
            {
                midcom::get()->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get($e->getMessage()), 'error');
                return;
            }

            //add rule was generated with wizard
            $rules['generated'] = 'wizard';

            //resolve rules
            $solver = new org_openpsa_directmarketing_campaign_ruleresolver();
            $solver->resolve($rules);
            $rule_persons = $solver->execute();

            //if it's not preview update campaign & Schedule background members refresh'
            if (!isset($_POST['show_rule_preview']))
            {
                $this->_campaign->rules = $rules;
                if (!$this->_campaign->update())
                {
                    //Save failed
                    midcom::get()->uimessages->add('org.openpsa.directmarketing', sprintf($this->_l10n->get('error when saving rule, errstr: %s'), midcom_connection::get_error_string()), 'error');
                    return;
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
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/edit_query_advanced/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('advanced rule editor'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
            )
        );

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.css');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/list.css');

        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        midcom::get()->head->set_pagetitle($this->_campaign->title);
        $this->bind_view_to_object($this->_campaign);
        $this->_update_breadcrumb_line($handler_id);
    }

    private function _load_rules_from_post()
    {
        if (empty($_POST['midcom_helper_datamanager2_dummy_field_rules']))
        {
            throw new midcom_error('no rule given');
        }
        $eval = '$tmp_array = ' . $_POST['midcom_helper_datamanager2_dummy_field_rules'] . ';';
        $eval_ret = eval($eval);

        if (   $eval_ret === false
            || !is_array($tmp_array))
        {
            throw new midcom_error('given rule could not be parsed');
        }
        if (count($tmp_array) == 0)
        {
            throw new midcom_error('given rule is empty');
        }
        return $tmp_array;
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
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit_query_advanced($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);
        $this->_campaign->require_do('midgard:update');

        $this->_prepare_request_data($handler_id);

        if (!empty($_POST['midcom_helper_datamanager2_cancel']))
        {
            return new midcom_response_relocate("campaign/" . $this->_campaign->guid . '/');
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.css');

        if (!empty($_POST['midcom_helper_datamanager2_save']))
        {
            try
            {
                $rules = $this->_load_rules_from_post();
            }
            catch (midcom_error $e)
            {
                midcom::get()->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get($e->getMessage()), 'error');
                return;
            }
            //Actual save routine
            if (array_key_exists('generated_from',  $rules))
            {
                debug_add('"generated_from" found in advanced rule, removing', MIDCOM_LOG_WARN);
                unset ($rules['generated_from']);
                // PONDER: return to editor or save anyway ? now we overwrite the value with the modified rule and return to editor.
                midcom::get()->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get('longtext:generated_from_found_in_adv_rule'), 'error');
                $_POST['midcom_helper_datamanager2_dummy_field_rules'] = var_export($rules, true);

                $this->_update_breadcrumb_line($handler_id);
                org_openpsa_helpers::dm2_savecancel($this);
                return;
            }
            $this->_campaign->rules = $rules;
            if (!$this->_campaign->update())
            {
                //Save failed
                midcom::get()->uimessages->add('org.openpsa.directmarketing', sprintf($this->_l10n->get('error when saving rule, errstr: %s'), midcom_connection::get_error_string()), 'error');
                return;
            }

            //Schedule background members refresh
            $this->_campaign->schedule_update_smart_campaign_members();

            //Save ok, relocate
            return new midcom_response_relocate("campaign/" . $this->_campaign->guid . '/');
        }

        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        midcom::get()->head->set_pagetitle($this->_campaign->title);

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
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
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
                //$indexer = midcom::get()->indexer;
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
        $this->_campaign->require_do('midgard:delete');

        $controller = midcom_helper_datamanager2_handler::get_delete_controller();
        if ($controller->process_form() == 'delete')
        {
            if (! $this->_campaign->delete())
            {
                throw new midcom_error("Failed to delete campaign {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            $indexer = midcom::get()->indexer;
            $indexer->delete($this->_campaign->guid);
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $this->_campaign->title));
            return new midcom_response_relocate('');
        }

        return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
    }
}
?>
