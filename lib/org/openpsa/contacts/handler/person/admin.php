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
     * The Datamanager of the contact to display (for delete mode)
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

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
    private function _prepare_request_data($handler_id)
    {
        $this->_request_data['person'] = $this->_contact;
        $this->_request_data['datamanager'] = $this->_datamanager;
        $this->_request_data['controller'] = $this->_controller;

        if ($handler_id !== 'person_edit')
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "person/edit/{$this->_contact->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_contact->can_do('midgard:update'),
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }
        if ($handler_id !== 'person_delete')
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "person/delete/{$this->_contact->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ENABLED => $this->_contact->can_do('midgard:delete'),
                    MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                )
            );
        }
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
     * Internal helper, loads the datamanager for the current contact. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (! $this->_datamanager->autoset_storage($this->_contact))
        {
            throw new midcom_error("Failed to create a DM2 instance for contact {$this->_contact->id}.");
        }
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
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param string $handler_id
     */
    private function _update_breadcrumb_line($handler_id)
    {
        $this->add_breadcrumb("person/{$this->_contact->guid}/", $this->_contact->name);

        switch ($handler_id)
        {
            case 'person_edit':
                $this->add_breadcrumb("person/edit/{$this->_contact->guid}/", $this->_l10n_midcom->get('edit'));
                break;
            case 'person_delete':
                $this->add_breadcrumb("person/delete/{$this->_contact->guid}/", $this->_l10n_midcom->get('delete'));
                break;
        }
    }

    /**
     * Displays a contact edit view.
     *
     * Note, that the contact for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation contact
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

        org_openpsa_helpers::dm2_savecancel($this);

        $this->_prepare_request_data($handler_id);
        midcom::get()->head->set_pagetitle($this->_contact->name);
        $this->bind_view_to_object($this->_contact, $this->_controller->datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);
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
        $this->_contact->require_do('midgard:delete');

        $controller = midcom_helper_datamanager2_handler::get_delete_controller();
        if ($controller->process_form() == 'delete')
        {
            if (! $this->_contact->delete())
            {
                throw new midcom_error("Failed to delete contact {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
            }

            $indexer = midcom::get()->indexer;
            $indexer->delete($this->_contact->guid . '_' . $this->_i18n->get_content_language());
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $this->_contact->get_label()));
            return new midcom_response_relocate('');
        }

        return new midcom_response_relocate("person/{$this->_contact->guid}/");
    }
}
?>