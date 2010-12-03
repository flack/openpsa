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
class org_openpsa_contacts_handler_group_privileges extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The group we're working with, if any
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_group = null;

    private function _load_group($identifier)
    {
        $group = new org_openpsa_contacts_group_dba($identifier);

        if (!is_object($group))
        {
            debug_add("Group object {$identifier} is not an object");
            return false;
        }

        $_MIDCOM->set_pagetitle($group->official);

        return $group;
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_acl'));

        $fields =& $schemadb['default']->fields;

        $group_object = $_MIDCOM->auth->get_group("group:{$this->_request_data['group']->guid}");

        // Get the calendar root event
        $root_event = org_openpsa_calendar_interface::find_root_event();
        if ( is_object($root_event))
        {
            $fields['calendar']['privilege_object'] = $root_event;
            $fields['calendar']['privilege_assignee'] = $group_object->id;
        }
        else if (isset($fields['calendar']))
        {
            unset($fields['calendar']);
        }

        // Set the group into ACL
        $fields['contact_creation']['privilege_object'] =  $group_object->get_storage();
        $fields['contact_editing']['privilege_object'] =  $group_object->get_storage();

        $fields['organization_creation']['privilege_object'] = $group_object->get_storage();
        $fields['organization_editing']['privilege_object'] = $group_object->get_storage();

        $fields['projects']['privilege_object'] = $group_object->get_storage();
        $fields['invoices_creation']['privilege_object'] = $group_object->get_storage();
        $fields['invoices_editing']['privilege_object'] = $group_object->get_storage();
        // Load campaign classes
        if ($_MIDCOM->componentloader->load_graceful('org.openpsa.directmarketing'))
        {
            $fields['campaigns_creation']['privilege_object'] = $group_object->get_storage();
            $fields['campaigns_editing']['privilege_object'] = $group_object->get_storage();
        }
        else
        {
            unset($fields['campaigns_creation']);
            unset($fields['campaigns_editing']);
        }
        $fields['salesproject_creation']['privilege_object'] = $group_object->get_storage();
        return $schemadb;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_privileges($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // Check if we get the group
        $this->_group = $this->_load_group($args[0]);
        if (!$this->_group)
        {
            debug_add("Group loading failed");
            return false;
        }

        $_MIDCOM->auth->require_do('midgard:privileges', $this->_group);

        $data['group'] =& $this->_group;

        $data['acl_dm'] = $this->get_controller('simple', $this->_group);

        switch ($data['acl_dm']->process_form())
        {
            case 'save':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                    . "group/" . $this->_group->guid . "/");
                // This will exit()

            case 'cancel':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                    . "group/" . $this->_group->guid . "/");
                // This will exit()
        }

        org_openpsa_helpers::dm2_savecancel($this);

        $this->add_breadcrumb("group/{$this->_group->guid}/", $this->_group->name);
        $this->add_breadcrumb("", $this->_l10n->get('permissions'));

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_privileges($handler_id, &$data)
    {
        midcom_show_style("show-privileges");
    }
}
?>