<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: campaign.php 25716 2010-04-20 22:57:24Z flack $
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
     * @access private
     */
    private $_campaign = null;

    /**
     * Internal helper, loads the datamanager for the current campaign. Any error triggers a 500.
     *
     * @access private
     */
    private function _load_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        if (!$this->_datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for campaigns.");
            // This will exit.
        }
    }

    /**
     * Looks up an campaign to display.
     */
    function _handler_view ($handler_id, $args, &$data)
    {
        $this->_campaign = new org_openpsa_directmarketing_campaign_dba($args[0]);

        if (   !$this->_campaign
            || $this->_campaign->node != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The campaign {$args[0]} was not found.");
            // This will exit.
        }

        $_MIDCOM->load_library('org.openpsa.qbpager');
        $_MIDCOM->load_library('org.openpsa.contactwidget');

        $this->_load_datamanager();
        $this->_datamanager->autoset_storage($this->_campaign);

        $this->set_active_leaf('campaign_' . $this->_campaign->id);

        $this->_request_data['campaign'] =& $this->_campaign;
        $this->_request_data['datamanager'] =& $this->_datamanager;

        // Populate the toolbar
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

        if ($this->_campaign->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART)
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
                    MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba'),
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
        $_MIDCOM->set_26_request_metadata($this->_campaign->metadata->revised, $this->_campaign->guid);
        $_MIDCOM->set_pagetitle($this->_campaign->title);

        return true;
    }

    /**
     * Shows the loaded campaign.
     */
    function _show_view ($handler_id, &$data)
    {
        $data['view_campaign'] = $this->_datamanager->get_content_html();

        // List members of this campaign
        $qb = new org_openpsa_qbpager_direct('org_openpsa_campaign_member', 'campaign_members');
        $qb->add_constraint('campaign', '=', $data['campaign']->id);
        $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
        $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_UNSUBSCRIBED);

        // Set the order
        $qb->add_order('person.lastname', 'ASC');
        $qb->add_order('person.firstname', 'ASC');
        $qb->add_order('person.username', 'ASC');
        $qb->add_order('person.id', 'ASC');

        $data['campaign_members_qb'] =& $qb;
        $data['memberships'] = $qb->execute_unchecked();
        $data['campaign_members_count'] =  $qb->count_unchecked();

        $data['campaign_members'] = array();
        if (!empty($data['memberships']))
        {
            foreach ($data['memberships'] as $k => $membership)
            {
                $data['campaign_members'][$k] = new midcom_db_person($membership->person);
            }
        }

        midcom_show_style('show-campaign');
    }
}
?>