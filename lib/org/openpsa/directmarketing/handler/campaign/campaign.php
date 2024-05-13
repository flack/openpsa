<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use midcom\grid\provider\client;
use midcom\grid\provider;

/**
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_campaign_campaign extends midcom_baseclasses_components_handler
implements client
{
    use org_openpsa_directmarketing_handler;

    private org_openpsa_directmarketing_campaign_dba $_campaign;

    private array $memberships;

    public function get_qb(?string $field = null, string $direction = 'ASC', array $search = []) : midcom_core_query
    {
        $mc = org_openpsa_directmarketing_campaign_member_dba::new_collector('campaign', $this->_campaign->id);
        $mc->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);
        $mc->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);
        $mc->add_constraint('person.metadata.deleted', '=', false);
        midcom::get()->auth->request_sudo($this->_component);
        $this->memberships = $mc->get_rows(['orgOpenpsaObtype', 'guid'], 'person');
        midcom::get()->auth->drop_sudo();

        $query = org_openpsa_contacts_person_dba::new_query_builder();
        $query->add_constraint('id', 'IN', array_keys($this->memberships));

        if ($field !== null) {
            $query->add_order($field, $direction);
        }
        // Set the order
        $query->add_order('lastname', 'ASC');
        $query->add_order('firstname', 'ASC');
        $query->add_order('email', 'ASC');

        return $query;
    }

    public function get_row(midcom_core_dbaobject $person) : array
    {
        $template = '%s';
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        if ($url = $siteconfig->get_node_full_url('org.openpsa.contacts') . 'person/') {
            $template = '<a target="_blank" href="' . $url . $person->guid . '">%s</a>';
        }

        $row = [
            'id' => $person->id,
            'index_firstname' => $person->firstname,
            'firstname' => sprintf($template, $person->firstname),
            'index_lastname' => $person->lastname,
            'lastname' => sprintf($template, $person->lastname),
            'index_email' => $person->email,
            'email' => sprintf($template, $person->email)
        ];

        $delete_string = sprintf($this->_l10n->get('remove %s from campaign'), $person->name);
        $row['delete'] = '<i class="fa fa-trash" data-person-guid="' . $person->guid . '" data-member-guid="' . $this->memberships[$person->id]['guid'] . '" title="' . $delete_string . '"></i>';

        $row['bounced'] = '';
        if ($this->memberships[$person->id]['orgOpenpsaObtype'] == org_openpsa_directmarketing_campaign_member_dba::BOUNCED) {
            $row['bounced'] = '<i class="fa fa-exclamation-triangle" title="' . sprintf($this->_l10n->get('%s has bounced'), $person->email) . '"></i>';
        }

        return $row;
    }

    /**
     * Looks up an campaign to display.
     */
    public function _handler_view(string $guid, array &$data)
    {
        $this->_campaign = $this->load_campaign($guid);

        $datamanager = datamanager::from_schemadb($this->_config->get('schemadb_campaign'));
        $datamanager->set_storage($this->_campaign);

        $data['campaign'] = $this->_campaign;

        $this->_populate_toolbar();

        $provider = new provider($this);
        $data['grid'] = $provider->get_grid('list_members_' . $guid);

        // Populate calendar events for the campaign
        $this->bind_view_to_object($this->_campaign, $datamanager->get_schema()->get_name());
        midcom::get()->metadata->set_request_metadata($this->_campaign->metadata->revised, $guid);
        midcom::get()->head->set_pagetitle($this->_campaign->title);
        $data['view_campaign'] = $datamanager->get_content_html();

        return $this->show('show-campaign');
    }

    private function _populate_toolbar()
    {
        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if ($this->_campaign->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button($this->router->generate('edit_campaign', ['guid' => $this->_campaign->guid]), [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }

        if ($this->_campaign->can_do('midgard:delete')) {
            $delete_workflow = $this->get_workflow('delete', ['object' => $this->_campaign]);
            $buttons[] = $delete_workflow->get_button($this->router->generate('delete_campaign', ['guid' => $this->_campaign->guid]));
        }

        if ($this->_campaign->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_dba::TYPE_SMART) {
            //Edit query parameters button in case 1) not in edit mode 2) is smart campaign 3) can edit
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $this->router->generate('edit_campaign_query', ['guid' => $this->_campaign->guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit rules'),
                MIDCOM_TOOLBAR_GLYPHICON => 'filter',
                MIDCOM_TOOLBAR_ENABLED => $this->_campaign->can_do('midgard:update'),
            ];
        } else {
            // Import button if we have permissions to create users
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $this->router->generate('import_main', ['guid' => $this->_campaign->guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('import subscribers'),
                MIDCOM_TOOLBAR_GLYPHICON => 'upload',
                MIDCOM_TOOLBAR_ENABLED => midcom::get()->auth->can_user_do('midgard:create', class: org_openpsa_contacts_person_dba::class),
            ];
        }
        $buttons[] = [
            MIDCOM_TOOLBAR_URL => $this->router->generate('export_csv', ['guid' => $this->_campaign->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('export as csv'),
            MIDCOM_TOOLBAR_GLYPHICON => 'download',
        ];

        if ($this->_campaign->can_do('midgard:create')) {
            $schemadb = schemadb::from_path($this->_config->get('schemadb_message'));
            foreach ($schemadb->all() as $name => $schema) {
                $buttons[] = $workflow->get_button($this->router->generate('create_message', [
                    'campaign' => $this->_campaign->guid,
                    'schema' => $name
                ]), [
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n->get('new %s'), $this->_l10n->get($schema->get('description'))),
                    MIDCOM_TOOLBAR_GLYPHICON => 'envelope-o'
                ]);
            }
        }
        $this->_view_toolbar->add_items($buttons);
    }

    /**
     * Displays campaign members.
     */
    public function _handler_members(string $guid, array &$data)
    {
        $this->_campaign = $this->load_campaign($guid);

        midcom::get()->skip_page_style = true;

        $data['provider'] = new provider($this);
        return $this->show('show-campaign-members');
    }
}
