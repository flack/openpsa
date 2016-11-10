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
implements org_openpsa_widgets_grid_provider_client
{
    /**
     * The campaign which has been created
     *
     * @var org_openpsa_directmarketing_campaign
     */
    private $_campaign = null;

    /**
     *
     * @var array
     */
    private $memberships;

    /**
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager;

    public function get_qb($field = null, $direction = 'ASC', array $search = array())
    {
        $mc = org_openpsa_directmarketing_campaign_member_dba::new_collector('campaign', $this->_campaign->id);
        $mc->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);
        $mc->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);
        $mc->add_constraint('person.metadata.deleted', '=', false);
        midcom::get()->auth->request_sudo($this->_component);
        $this->memberships = $mc->get_rows(array('orgOpenpsaObtype', 'guid'), 'person');
        midcom::get()->auth->drop_sudo();
        $query = org_openpsa_contacts_person_dba::new_query_builder();
        $query->add_constraint('id', 'IN', array_keys($this->memberships));
        if (!is_null($field)) {
            $query->add_order($field, $direction);
        }
        // Set the order
        $query->add_order('lastname', 'ASC');
        $query->add_order('firstname', 'ASC');
        $query->add_order('email', 'ASC');

        return $query;
    }

    public function get_row(midcom_core_dbaobject $person)
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $url = $siteconfig->get_node_full_url('org.openpsa.contacts') . 'person/';

        $row = array(
            'id' => $person->id,
            'index_firstname' => $person->firstname,
            'firstname' => '<a target="_blank" href="' . $url . $person->guid . '/">' . $person->firstname . '</a>',
            'index_lastname' => $person->lastname,
            'lastname' => '<a target="_blank" href="' . $url . $person->guid . '/">' . $person->lastname . '</a>',
            'index_email' => $person->email,
            'email' => '<a target="_blank" href="' . $url . $person->guid . '/">' . $person->email . '</a>'
        );

        $delete_string = sprintf($this->_l10n->get('remove %s from campaign'), $person->name);
        $row['delete'] = '<input type="image" src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/trash.png" data-person-guid="' . $person->guid . '" data-member-guid="' . $this->memberships[$person->id]['guid'] . '" title="' . $delete_string . '"/>';

        $row['bounced'] = '';
        if ($this->memberships[$person->id]['orgOpenpsaObtype'] == org_openpsa_directmarketing_campaign_member_dba::BOUNCED) {
            $row['bounced'] = '<span class="icon icon-bounced" title="' . sprintf($this->_l10n->get('%s has bounced'), $person->email) . '"></span>';
        }

        return $row;
    }

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
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);

        $this->_load_datamanager();
        $this->_datamanager->autoset_storage($this->_campaign);

        $this->_request_data['campaign'] = $this->_campaign;
        $this->_request_data['datamanager'] = $this->_datamanager;

        org_openpsa_widgets_contact::add_head_elements();
        $this->_populate_toolbar();

        $provider = new org_openpsa_widgets_grid_provider($this);
        $data['grid'] = $provider->get_grid('list_members_' . $this->_campaign->guid);

        // Populate calendar events for the campaign
        $this->bind_view_to_object($this->_campaign, $this->_datamanager->schema->name);
        midcom::get()->metadata->set_request_metadata($this->_campaign->metadata->revised, $this->_campaign->guid);
        midcom::get()->head->set_pagetitle($this->_campaign->title);
    }

    private function _populate_toolbar()
    {
        $workflow = $this->get_workflow('datamanager2');
        $buttons = array();
        if ($this->_campaign->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button("campaign/edit/{$this->_campaign->guid}/", array(
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ));
        }

        if ($this->_campaign->can_do('midgard:delete')) {
            $delete_workflow = $this->get_workflow('delete', array('object' => $this->_campaign));
            $buttons[] = $delete_workflow->get_button("campaign/delete/{$this->_campaign->guid}/");
        }

        if ($this->_campaign->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_dba::TYPE_SMART) {
            //Edit query parameters button in case 1) not in edit mode 2) is smart campaign 3) can edit
            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "campaign/edit_query/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit rules'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:update'),
            );
        } else {
            // Import button if we have permissions to create users
            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => "campaign/import/{$this->_campaign->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('import subscribers'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba'),
            );
        }
        $buttons[] = array(
            MIDCOM_TOOLBAR_URL => "campaign/export/csv/{$this->_campaign->guid}/",
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('export as csv'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_data-edit-table.png',
        );

        if ($this->_campaign->can_do('midgard:create')) {
            $schemadb_message = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
            foreach ($schemadb_message as $name => $schema) {
                $buttons[] = $workflow->get_button("message/create/{$this->_campaign->guid}/{$name}/", array(
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('new %s'), $this->_l10n->get($schema->description)),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/' . org_openpsa_directmarketing_viewer::get_messagetype_icon($schema->customdata['org_openpsa_directmarketing_messagetype']),
                ));
            }
        }
        $this->_view_toolbar->add_items($buttons);
    }

    /**
     * Shows the loaded campaign.
     */
    public function _show_view($handler_id, array &$data)
    {
        $data['view_campaign'] = $this->_datamanager->get_content_html();
        midcom_show_style('show-campaign');
    }

    /**
     * Displays campaign members.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_members($handler_id, array $args, array &$data)
    {
        $this->_campaign = $this->_master->load_campaign($args[0]);

        midcom::get()->skip_page_style = true;
    }

    /**
     * Shows campaign members.
     */
    public function _show_members($handler_id, array &$data)
    {
        $data['provider'] = new org_openpsa_widgets_grid_provider($this);
        midcom_show_style('show-campaign-members');
    }
}
