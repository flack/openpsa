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
implements midcom_helper_datamanager2_interfaces_view
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

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // Get the requested group object
        $this->_group = new org_openpsa_contacts_group_dba($args[0]);
        $data['group'] =& $this->_group;

        if ($this->_group->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_OTHERGROUP)
        {
            $this->_type = 'group';
            $data['group_tree'] = $this->_master->get_group_tree();
            $data['members_grid'] = new org_openpsa_widgets_grid('members_grid', 'local');
            $data['members'] = $this->_load_members();
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

    private function _load_members()
    {
        $rows = array();

        $mc = midcom_db_member::new_collector('gid', $this->_group->id);
        $ids = $mc->get_values('uid');
        if (empty($ids))
        {
            return $rows;
        }
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', $ids);
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
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_group),
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

        if (   $_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba')
            && $_MIDCOM->auth->can_do('midgard:create', $this->_group))
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
}
?>