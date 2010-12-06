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
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_group'));
    }

    private function _load($identifier)
    {
        $this->_group = new org_openpsa_contacts_group_dba($identifier);
        if (   !$this->_group
            || !$this->_group->guid)
        {
            return false;
        }
        return true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_view($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // Get the requested group object
        if (!$this->_load($args[0]))
        {
            return false;
        }

        $data['view'] = midcom_helper_datamanager2_handler::get_view($this, $this->_group);
        $data['group'] =& $this->_group;

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
            $qb_billing_data->add_constraint('linkGuid' , '=' , $this->_group->guid);
            $billing_data = $qb_billing_data->execute();
            if (count($billing_data) > 0)
            {
                $this->_request_data['billing_data'] = $billing_data[0];
            }
        }

        // Add toolbar items
        $this->_populate_toolbar();
        $_MIDCOM->bind_view_to_object($this->_group);

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/ui-elements.css");
        // This handler uses Ajax, include the handler javascripts
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.helpers/ajaxutils.js");
        org_openpsa_core_ui::enable_ui_tab();

        $_MIDCOM->set_pagetitle($this->_group->official);

        org_openpsa_contacts_viewer::add_breadcrumb_path_for_group($this->_group, $this);

        return true;
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
                MIDCOM_TOOLBAR_URL => "group/create/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create suborganization'),
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

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "group/notifications/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("notification settings"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock-discussion.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_group),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "group/privileges/{$this->_group->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("permissions"),
                MIDCOM_TOOLBAR_ICON => 'midgard.admin.asgard/permissions-16.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:privileges', $this->_group),
            )
        );

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
     * @param mixed &$data The local request data.
     */
    public function _show_view($handler_id, &$data)
    {
        midcom_show_style("show-group");
    }
}
?>