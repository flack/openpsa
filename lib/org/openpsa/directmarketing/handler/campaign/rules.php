<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * directmarketing campaign rules handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_campaign_rules extends midcom_baseclasses_components_handler
{
    /**
     * The campaign to operate on
     *
     * @var org_openpsa_directmarketing_campaign
     */
    private $_campaign;

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    private function _update_breadcrumb_line()
    {
        $this->add_breadcrumb("campaign/edit_query/{$this->_campaign->guid}/", $this->_l10n->get('edit rules'));
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['campaign'] = $this->_campaign;
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

        $this->_prepare_request_data();

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

            //if it's not preview update campaign & Schedule background members refresh'
            if (!isset($_POST['show_rule_preview']))
            {
                $this->_campaign->rules = $rules;
                if (!$this->_campaign->update())
                {
                    //Save failed
                    midcom::get()->uimessages->add($this->_component, sprintf($this->_l10n->get('error when saving rule, errstr: %s'), midcom_connection::get_error_string()), 'error');
                    return;
                }
                //Schedule background members refresh
                $this->_campaign->schedule_update_smart_campaign_members();

                //Save ok, relocate
                return new midcom_response_relocate("campaign/{$this->_campaign->guid}/");
            }

            //resolve rules
            $solver = new org_openpsa_directmarketing_campaign_ruleresolver();
            $solver->resolve($rules);

            //set data for preview & skip page_style because of javascript call
            $data['preview_persons'] = $solver->execute();
            midcom::get()->skip_page_style = true;
        }

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "#",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('advanced rule editor'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                MIDCOM_TOOLBAR_OPTIONS  => array
                (
                    'id' => 'openpsa_dirmar_edit_query_advanced',
                ),
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/edit_query/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit rules'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                MIDCOM_TOOLBAR_OPTIONS  => array
                (
                    'id' => 'openpsa_dirmar_edit_query',
                ),
            )
        );

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.css');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/list.css');

        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        midcom::get()->head->set_pagetitle($this->_campaign->title);
        $this->bind_view_to_object($this->_campaign);
        $this->_update_breadcrumb_line();
    }

    private function _load_rules_from_post()
    {
        if (empty($_POST['midcom_helper_datamanager2_dummy_field_rules']))
        {
            throw new midcom_error('no rule given');
        }
        return org_openpsa_directmarketing_campaign_ruleresolver::parse($_POST['midcom_helper_datamanager2_dummy_field_rules']);
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
}
