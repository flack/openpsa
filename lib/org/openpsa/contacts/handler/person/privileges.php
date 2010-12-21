<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts person handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_privileges extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The person we're working with, if any
     *
     * @var org_openpsa_contacts_person_dba
     */
    private $_person = null;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_acl'));

        $fields =& $schemadb['default']->fields;
        $user_object = $_MIDCOM->auth->get_user($this->_person->guid);

        $person_object = $user_object->get_storage();

        // Get the calendar root event
        $root_event = org_openpsa_calendar_interface::find_root_event();
        if (is_object($root_event))
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
        return $schemadb;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_privileges($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // Check if we get the person
        $this->_person = $this->load_object('org_openpsa_contacts_person_dba', $args[0]);
        $_MIDCOM->auth->require_do('midgard:privileges', $this->_person);
        $this->_request_data['person'] =& $this->_person;

        $data['acl_dm'] = $this->get_controller('simple', $this->_person);

        switch ($data['acl_dm']->process_form())
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

        $_MIDCOM->set_pagetitle("{$this->_person->firstname} {$this->_person->lastname}");
        org_openpsa_helpers::dm2_savecancel($this);

        $this->add_breadcrumb("person/{$this->_person->guid}/", $this->_person->name);
        $this->add_breadcrumb('', $this->_l10n->get('permissions'));
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