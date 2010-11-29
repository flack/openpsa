<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: privileges.php 26174 2010-05-24 19:10:58Z flack $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts person handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_privileges extends midcom_baseclasses_components_handler
{
    /**
     * The Controller of the contact used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * Schema to use for display
     *
     * @var string
     */
    private $_schema = 'default';

    /**
     * The person we're working with, if any
     *
     * @var org_openpsa_contacts_person_dba
     */
    private $_person = null;

    function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    function _load_person($identifier)
    {
        $person = new org_openpsa_contacts_person_dba($identifier);

        if (!is_object($person))
        {
            debug_add("Person object {$identifier} is not an object");
            return false;
        }

        $_MIDCOM->set_pagetitle("{$person->firstname} {$person->lastname}");

        return $person;
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
        $user_object = $_MIDCOM->auth->get_user($this->_person->guid);

	$person_object = $user_object->get_storage();

        // Get the calendar root event
        $root_event = org_openpsa_calendar_interface::find_root_event();
        if ( is_object($root_event))
        {
            $fields['calendar']['privilege_object'] = $root_event;
            $fields['calendar']['privilege_assignee'] = $user_object->id;
        }
        else if (isset($fields['calendar']))
        {
            unset($fields['calendar']);
        }


        $fields['contact_creation']['privilege_object'] =  $person_object;
        $fields['contact_editing']['privilege_object'] =  $person_object;

        $fields['organization_creation']['privilege_object'] = $person_object;
        $fields['organization_editing']['privilege_object'] = $person_object;

        $fields['projects']['privilege_object'] = $person_object;
        $fields['invoices_creation']['privilege_object'] = $person_object;
        $fields['invoices_editing']['privilege_object'] = $person_object;

        $fields['products_creation']['privilege_object'] = $person_object;
        $fields['products_editing']['privilege_object'] = $person_object;

        // Load wiki classes
        if ($_MIDCOM->componentloader->load_graceful('net.nemein.wiki'))
        {
            $fields['wiki_creation']['privilege_object'] = $person_object;
            $fields['wiki_editing']['privilege_object'] = $person_object;
        }
        else
        {
            unset($fields['wiki_creation']);
            unset($fields['wiki_editing']);
        }
        // Load campaign classes
        if ($_MIDCOM->componentloader->load_graceful('org.openpsa.directmarketing'))
        {
            $fields['campaigns_creation']['privilege_object'] = $person_object;
            $fields['campaigns_editing']['privilege_object'] = $person_object;
        }
        else
        {
            unset($fields['campaigns_creation']);
            unset($fields['campaigns_editing']);
        }
        $fields['salesproject_creation']['privilege_object'] = $person_object;
    }

    /**
     * Internal helper, loads the controller for the current contact. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_person, $this->_schema);
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

        // Check if we get the person
        $this->_person = $this->_load_person($args[0]);
        if (!$this->_person)
        {
            debug_add("Person loading failed");
            return false;
        }

        $_MIDCOM->auth->require_do('midgard:privileges', $this->_person);

        $this->_request_data['person'] =& $this->_person;

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                    . "person/" . $this->_person->guid . "/");
                // This will exit()

            case 'cancel':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                    . "person/" . $this->_person->guid . "/");
                // This will exit()
        }

        org_openpsa_helpers::dm2_savecancel($this);

        $this->add_breadcrumb("person/{$this->_person->guid}/", $this->_person->name);
        $this->add_breadcrumb('', $this->_l10n->get('permissions'));

        return true;
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