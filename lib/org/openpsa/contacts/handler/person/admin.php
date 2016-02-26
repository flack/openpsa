<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Contacts edit/delete person handler
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_admin extends midcom_baseclasses_components_handler
{
    /**
     * The contact to operate on
     *
     * @var org_openpsa_contacts_contact_dba
     */
    private $_contact = null;

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
     * Schema to use for contact display
     *
     * @var string
     */
    private $_schema = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['person'] = $this->_contact;
        $this->_request_data['controller'] = $this->_controller;
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
        $this->_schema = $this->_master->get_person_schema($this->_contact);
    }

    /**
     * Internal helper, loads the controller for the current contact. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_contact, $this->_schema);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for contact {$this->_contact->id}.");
        }
    }

    /**
     * Displays a contact edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_contact = new org_openpsa_contacts_person_dba($args[0]);
        $this->_contact->require_do('midgard:update');

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the contact
                $indexer = new org_openpsa_contacts_midcom_indexer($this->_topic);
                $indexer->index($this->_controller->datamanager);

                // *** FALL-THROUGH ***
            case 'cancel':
                return new midcom_response_relocate("person/{$this->_contact->guid}/");
        }

        $this->_prepare_request_data();
        midcom::get()->head->set_pagetitle($this->_contact->name);
        $this->bind_view_to_object($this->_contact, $this->_controller->datamanager->schema->name);
        $this->add_breadcrumb("person/{$this->_contact->guid}/", $this->_contact->name);
        $this->add_breadcrumb("person/edit/{$this->_contact->guid}/", $this->_l10n_midcom->get('edit'));
    }

    /**
     * Shows the loaded contact.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit ($handler_id, array &$data)
    {
        midcom_show_style('show-person-edit');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_contact = new org_openpsa_contacts_person_dba($args[0]);
        $workflow = new midcom\workflow\delete($this->_contact);
        return $workflow->run();
    }
}
