<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: privileges.php 25318 2010-03-18 12:16:52Z indeyets $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts group handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_group_privileges extends midcom_baseclasses_components_handler
{
    /**
     * The Datamanager of the contact to display (for delete mode)
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    private $_datamanager;

    /**
     * The Controller of the contact used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     * @access private
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     * @access private
     */
    private $_schemadb = null;

    /**
     * Schema to use for display
     *
     * @var string
     * @access private
     */
    private $_schema = 'default';

    /**
     * The group we're working with, if any
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_group = null;

    function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }


    function _load_group($identifier)
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
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_acl'));
        $this->_modify_schema();
    }

    /**
     * Helper function to alter the schema based on the current operation
     */
    private function _modify_schema()
    {
        $fields =& $this->_schemadb['default']->fields;

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

    }

    /**
     * Internal helper, loads the controller for the current contact. Any error triggers a 500.
     *
     * @access private
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_group, $this->_schema);
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for contact {$this->_contact->id}.");
            // This will exit.
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_privileges($handler_id, $args, &$data)
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

        $this->_request_data['group'] =& $this->_group;

        $this->_load_controller();

        switch ($this->_controller->process_form())
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

        $this->_update_breadcrumb_line();

        return true;
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    private function _update_breadcrumb_line()
    {
        $tmp = Array();

        $tmp[] = array
        (
            MIDCOM_NAV_URL => "group/{$this->_group->guid}/",
            MIDCOM_NAV_NAME => $this->_group->name,
        );

        $tmp[] = array
        (
            MIDCOM_NAV_URL => "",
            MIDCOM_NAV_NAME => $this->_l10n->get('permissions'),
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_privileges($handler_id, &$data)
    {
        $this->_request_data['acl_dm'] =& $this->_controller;
        midcom_show_style("show-privileges");
    }

}
?>