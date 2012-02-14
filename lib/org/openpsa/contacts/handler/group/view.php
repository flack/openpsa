<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_group_view extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_view, org_openpsa_widgets_grid_provider_client
{
    /**
     * What type of group are we dealing with, organization or group?
     *
     * @var string
     */
    private $_type;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    public function get_schema_name()
    {
        return $this->_type;
    }

    private function _load_members()
    {
        $rows = array();

        $member_ids = array_keys($this->_group->get_members());

        if (empty($member_ids))
        {
            return $rows;
        }
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', $member_ids);
        $qb->add_order('lastname');
        $qb->add_order('firstname');
        $qb->add_order('email');
        $members = $qb->execute();

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $prefix .= 'person/';

        foreach ($members as $person)
        {
            $row = array
            (
                'index_firstname' => $person->firstname,
                'firstname' => '<a href="' . $prefix . $person->guid . '/">' . $person->firstname . '</a>',
                'index_lastname' => $person->lastname,
                'lastname' => '<a href="' . $prefix . $person->guid . '/">' . $person->lastname . '</a>',
                'index_email' => $person->email,
                'email' =>  '<a href="mailto:' . $person->email . '">' . $person->email . '</a>',
            );
            $rows[] = $row;
        }
        return $rows;
    }

    private function _populate_toolbar()
    {
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "group/edit/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get("edit"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_group->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "group/create/organization/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create suborganization'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_group->can_do('midgard:update'),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "group/create/group/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create subgroup'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_group->can_do('midgard:update'),
            )
        );

        if (   midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba')
            && $this->_group->can_do('midgard:create'))
        {
            $allow_person_create = true;
        }
        else
        {
            $allow_person_create = false;
        }

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "person/create/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
                MIDCOM_TOOLBAR_ENABLED => $allow_person_create,
            )
        );

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $user_url = $siteconfig->get_node_full_url('org.openpsa.user');
        if (   $user_url
            && midcom::get('auth')->can_user_do('org.openpsa.user:access', null, 'org_openpsa_user_interface'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => $user_url . "group/{$this->_group->guid}/",
                    MIDCOM_TOOLBAR_LABEL => midcom::get('i18n')->get_string('user management', 'org.openpsa.user'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
                )
            );
        }

        $cal_node = midcom_helper_misc::find_node_by_component('org.openpsa.calendar');
        if (!empty($cal_node))
        {
            //TODO: Check for privileges somehow
            $this->_node_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "#",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create event'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_new-event.png',
                    MIDCOM_TOOLBAR_OPTIONS  => array
                    (
                        'rel' => 'directlink',
                        'onclick' => org_openpsa_calendar_interface::calendar_newevent_js($cal_node, false, $this->_group->guid),
                    ),
                )
            );
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        if ($this->_group->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_OTHERGROUP)
        {
            midcom_show_style("show-group-other");
        }
        else
        {
            midcom_show_style("show-group");
        }
    }

    /**
     * Handler method for listing group members
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        // Get the requested group object
        $this->_group = new org_openpsa_contacts_group_dba($args[0]);
        $data['group'] = $this->_group;

        if ($this->_group->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_OTHERGROUP)
        {
            $this->_type = 'group';
            $data['group_tree'] = $this->_master->get_group_tree();
            $data['members_grid'] = new org_openpsa_widgets_grid('members_grid', 'json');
            org_openpsa_widgets_tree::add_head_elements();
        }
        else
        {
            $this->_type = 'organization';
            $root_group = org_openpsa_contacts_interface::find_root_group();
            if ($this->_group->owner != $root_group->id)
            {
                $data['parent_group'] = $this->_group->get_parent();
            }
            else
            {
                $data['parent_group'] = false;
            }

            //pass billing-data if invoices is installed
            if ($_MIDCOM->componentloader->is_installed('org.openpsa.invoices'))
            {
                $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
                $qb_billing_data->add_constraint('linkGuid', '=', $this->_group->guid);
                $billing_data = $qb_billing_data->execute();
                if (count($billing_data) > 0)
                {
                    $this->_request_data['billing_data'] = $billing_data[0];
                }
            }
            // This handler uses Ajax, include the handler javascripts
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.helpers/ajaxutils.js");
            org_openpsa_widgets_ui::enable_ui_tab();
        }
        $data['view'] = midcom_helper_datamanager2_handler::get_view($this, $this->_group);

        // Add toolbar items
        $this->_populate_toolbar();

        $_MIDCOM->bind_view_to_object($this->_group);

        $_MIDCOM->set_pagetitle($this->_group->official);

        org_openpsa_contacts_viewer::add_breadcrumb_path_for_group($this->_group, $this);
    }

    /**
     * Lists group members in JSON format
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_json($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $_MIDCOM->skip_page_style = true;
        $this->_request_data['group'] = new org_openpsa_contacts_group_dba($args[0]);
    }

    /**
     * Show group members in JSON format
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_json($handler_id, array &$data)
    {
        $data['provider'] = new org_openpsa_widgets_grid_provider($this);
        midcom_show_style('show-group-json');
    }

    /**
     * Get querybuilder for JSON group member list
     */
    public function get_qb($field = null, $direction = 'ASC')
    {
        $qb = midcom_db_person::new_collector('metadata.deleted', false);
        $member_ids = array_keys($this->_request_data['group']->get_members());
        if (count($member_ids))
        {
            $qb->add_constraint('id', 'IN', $member_ids);
        }
        else
        {
            $qb->add_constraint('id', '=', 0);
        }

        if (!is_null($field))
        {
            $field = str_replace('index_', '', $field);
            if ($field == 'username')
            {
                midcom_core_account::add_username_order($qb, $direction);
            }
            else
            {
                $qb->add_order($field, $direction);
            }
        }
        $qb->add_order('lastname');
        $qb->add_order('firstname');
        $qb->add_order('email');
        $qb->add_order('id');
        return $qb;
    }

    /**
     * Prepares group member data for JSON display
     */
    public function get_row(midcom_core_dbaobject $user)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $entry = array();
        $entry['id'] = $user->id;
        $lastname = trim($user->lastname);
        if (empty($lastname))
        {
            $lastname = $this->_l10n->get('person') . ' #' . $user->id;
        }
        $entry['lastname'] = "<a href='" . $prefix . 'person/' . $user->guid . "/'>" . $lastname . "</a>";
        $entry['index_lastname'] = $lastname;
        $entry['firstname'] = "<a href='" . $prefix . 'person/' . $user->guid . "/' >" . $user->firstname . "</a>";
        $entry['index_firstname'] = $user->firstname;
        $account = new midcom_core_account($user);
        $entry['username'] = "<a href='" . $prefix . 'person/' . $user->guid . "/' >" . $account->get_username() . "</a>";
        $entry['index_username'] = $account->get_username();
        $entry['email'] = "<a href='mailto:" . $user->email . "' >" . $user->email . "</a>";
        $entry['index_email'] = $user->email;

        return $entry;
    }
}
?>