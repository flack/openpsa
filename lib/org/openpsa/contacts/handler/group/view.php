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
    private $type;

    private $group;

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
        return $this->type;
    }

    private function _populate_toolbar()
    {
        $workflow = $this->get_workflow('datamanager2');
        $buttons = array();
        if ($this->group->can_do('midgard:update')) {
            $buttons = array(
                $workflow->get_button("group/edit/{$this->group->guid}/", array(
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )),
                $workflow->get_button("group/create/organization/{$this->group->guid}/", array(
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create suborganization'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
                )),
                $workflow->get_button("group/create/group/{$this->group->guid}/", array(
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create subgroup'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
                ))
            );
        }

        if (   midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba')
            && $this->group->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button("person/create/{$this->group->guid}/", array(
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
            ));
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $user_url = $siteconfig->get_node_full_url('org.openpsa.user');
        if (   $user_url
            && midcom::get()->auth->can_user_do('org.openpsa.user:access', null, 'org_openpsa_user_interface')) {
            $buttons[] = array(
                MIDCOM_TOOLBAR_URL => $user_url . "group/{$this->group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_i18n->get_string('user management', 'org.openpsa.user'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            );
        }

        $cal_node = midcom_helper_misc::find_node_by_component('org.openpsa.calendar');
        if (!empty($cal_node)) {
            //TODO: Check for privileges somehow
            $buttons[] = org_openpsa_calendar_interface::get_create_button($cal_node, $this->group->guid . '/');
        }
        $this->_view_toolbar->add_items($buttons);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        if ($this->group->orgOpenpsaObtype < org_openpsa_contacts_group_dba::MYCONTACTS) {
            midcom_show_style('show-group-other');
        } else {
            midcom_show_style('show-group');
        }
    }

    /**
     * Handler for listing group members
     *
     * @param string $handler_id Name of the used handler
     * @param array $args Array containing the variable arguments passed to the handler
     * @param array &$data Data passed to the show method
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $this->group = new org_openpsa_contacts_group_dba($args[0]);
        $data['group'] = $this->group;

        if ($this->group->orgOpenpsaObtype < org_openpsa_contacts_group_dba::MYCONTACTS) {
            $this->type = 'group';
            $data['group_tree'] = $this->_master->get_group_tree();
            $data['members_grid'] = new org_openpsa_widgets_grid('members_grid', 'json');
            org_openpsa_widgets_tree::add_head_elements();
        } else {
            $this->type = 'organization';
            $root_group = org_openpsa_contacts_interface::find_root_group();
            if ($this->group->owner != $root_group->id) {
                $data['parent_group'] = $this->group->get_parent();
            } else {
                $data['parent_group'] = false;
            }

            $qb_billing_data = org_openpsa_invoices_billing_data_dba::new_query_builder();
            $qb_billing_data->add_constraint('linkGuid', '=', $this->group->guid);
            $billing_data = $qb_billing_data->execute();
            if (count($billing_data) > 0) {
                $this->_request_data['billing_data'] = $billing_data[0];
            }
            // This handler uses Ajax, include the handler javascripts
            midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.helpers/editable.js");
            org_openpsa_widgets_ui::enable_ui_tab();
        }
        $data['view'] = midcom_helper_datamanager2_handler::get_view($this, $this->group);

        // Add toolbar items
        $this->_populate_toolbar();

        $this->bind_view_to_object($this->group);

        midcom::get()->head->set_pagetitle($this->group->official);

        $this->add_breadcrumb_path_for_group();
    }

    private function add_breadcrumb_path_for_group()
    {
        $tmp = array(
            $this->group->guid => $this->group->official
        );

        $root_id = org_openpsa_contacts_interface::find_root_group()->id;
        $parent = $this->group->get_parent();

        while ($parent && $parent->id != $root_id) {
            $tmp[$parent->guid] = $parent->official;
            $parent = $parent->get_parent();
        }

        $tmp = array_reverse($tmp, true);

        foreach ($tmp as $guid => $title) {
            $this->add_breadcrumb('group/' . $guid . '/', $title);
        }
    }

    /**
     * Lists group members in JSON format
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_json($handler_id, array $args, array &$data)
    {
        midcom::get()->skip_page_style = true;
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
    public function get_qb($field = null, $direction = 'ASC', array $search = array())
    {
        $qb = midcom_db_person::new_collector('metadata.deleted', false);
        $member_ids = array_keys($this->_request_data['group']->get_members());
        $qb->add_constraint('id', 'IN', $member_ids);

        if (!is_null($field)) {
            $qb->add_order($field, $direction);
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
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $entry = array();
        $entry['id'] = $user->id;
        $lastname = trim($user->lastname);
        if (empty($lastname)) {
            $lastname = $this->_l10n->get('person') . ' #' . $user->id;
        }
        $entry['lastname'] = "<a href='" . $prefix . 'person/' . $user->guid . "/'>" . $lastname . "</a>";
        $entry['index_lastname'] = $lastname;
        $entry['firstname'] = "<a href='" . $prefix . 'person/' . $user->guid . "/' >" . $user->firstname . "</a>";
        $entry['index_firstname'] = $user->firstname;
        $entry['homepage'] = '';
        $entry['index_homepage'] = $user->homepage;
        if (!empty($user->homepage)) {
            $url = $user->homepage;
            if (!preg_match('/https?:\/\//', $url)) {
                $url = 'http://' . $user->homepage;
            }
            $entry['homepage'] = '<a href="' . $url . '">' . $user->homepage . '</a>';
        }

        $entry['email'] = "<a href='mailto:" . $user->email . "' >" . $user->email . "</a>";
        $entry['index_email'] = $user->email;

        return $entry;
    }
}
