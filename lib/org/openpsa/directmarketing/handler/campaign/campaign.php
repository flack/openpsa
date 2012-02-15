<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Discussion forum index
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_campaign_campaign extends midcom_baseclasses_components_handler
{
    /**
     * The campaign which has been created
     *
     * @var org_openpsa_directmarketing_campaign
     */
    private $_campaign = null;

    /**
     * Internal helper, loads the datamanager for the current campaign. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
    }

    /**
     * Looks up an campaign to display.
     */
    public function _handler_view ($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);

        $this->_load_datamanager();
        $this->_datamanager->autoset_storage($this->_campaign);

        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        $this->_request_data['campaign'] =& $this->_campaign;
        $this->_request_data['datamanager'] =& $this->_datamanager;

        org_openpsa_widgets_contact::add_head_elements();
        $this->_populate_toolbar();

        $schemadb_message = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
        foreach ($schemadb_message as $name => $schema)
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "message/create/{$this->_campaign->guid}/{$name}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('new %s'), $this->_l10n->get($schema->description)),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/' . org_openpsa_directmarketing_viewer::get_messagetype_icon($schema->customdata['org_openpsa_directmarketing_messagetype']),
                    MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:create'),
                )
            );
        }

        // Populate calendar events for the campaign
        $_MIDCOM->bind_view_to_object($this->_campaign, $this->_datamanager->schema->name);
        midcom::get('metadata')->set_request_metadata($this->_campaign->metadata->revised, $this->_campaign->guid);
        midcom::get('head')->set_pagetitle($this->_campaign->title);
    }

    private function _populate_toolbar()
    {
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/edit/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit campaign'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:update')
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/delete/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete campaign'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:delete')
            )
        );

        if ($this->_campaign->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_dba::TYPE_SMART)
        {
            //Edit query parameters button in case 1) not in edit mode 2) is smart campaign 3) can edit
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "campaign/edit_query/{$this->_campaign->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit rules'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:update'),
                )
            );
        }
        else
        {
            // Import button if we have permissions to create users
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "campaign/import/{$this->_campaign->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('import subscribers'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people.png',
                    MIDCOM_TOOLBAR_ENABLED => midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba'),
                )
            );
        }
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/export/csv/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('export as csv'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_data-edit-table.png',
            )
        );
    }

    /**
     * Shows the loaded campaign.
     */
    public function _show_view ($handler_id, array &$data)
    {
        $data['view_campaign'] = $this->_datamanager->get_content_html();

        // List members of this campaign
        $qb = new org_openpsa_qbpager_direct('org_openpsa_campaign_member', 'campaign_members');
        $qb->add_constraint('campaign', '=', $data['campaign']->id);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);
        $qb->add_constraint('person.metadata.deleted', '=', false);

        // Set the order
        $qb->add_order('person.lastname', 'ASC');
        $qb->add_order('person.firstname', 'ASC');
        $qb->add_order('person.email', 'ASC');
        $qb->add_order('person.id', 'ASC');

        $data['campaign_members_qb'] =& $qb;
        $data['memberships'] = $qb->execute_unchecked();
        $data['campaign_members_count'] =  $qb->count_unchecked();

        $data['campaign_members'] = array();
        if (!empty($data['memberships']))
        {
            foreach ($data['memberships'] as $k => $membership)
            {
                $data['campaign_members'][$k] = new org_openpsa_contacts_person_dba($membership->person);
            }
        }

        midcom_show_style('show-campaign');
    }
}
?>