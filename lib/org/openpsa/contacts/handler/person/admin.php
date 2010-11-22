<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: admin.php 25716 2010-04-20 22:57:24Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Contacts edit/delete person handler
 *
 * Originally copied from net.nehmer.blog
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_admin extends midcom_baseclasses_components_handler
{
    /**
     * The contact to operate on
     *
     * @var org_openpsa_contacts_contact_dba
     * @access private
     */
    private $_contact = null;

    /**
     * The Datamanager of the contact to display (for delete mode)
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    private $_datamanager = null;

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
     * Schema to use for contact display
     *
     * @var string
     * @access private
     */
    private $_schema = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data($handler_id)
    {
        $this->_request_data['person'] =& $this->_contact;
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['controller'] =& $this->_controller;

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "person/edit/{$this->_contact->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                MIDCOM_TOOLBAR_ENABLED => $this->_contact->can_do('midgard:update'),
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )
        );

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

        switch ($handler_id)
        {
            case 'person_edit':
                $this->_view_toolbar->disable_item("person/edit/{$this->_contact->guid}/");
                break;
            case 'person_delete':
                $this->_view_toolbar->disable_item("person/delete/{$this->_contact->guid}/");
                break;
        }
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css",
            )
        );
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $owner_guid = $siteconfig->get_my_company_guid();

        if ($owner_guid)
        {
            // Figure out if user is from own organization or other org
            $this->_request_data['person_user'] = new midcom_core_user($this->_contact->id);

            if ($this->_request_data['person_user']->is_in_group("group:{$owner_guid}"))
            {
                $this->_schema = 'employee';
            }
        }
    }

    /**
     * Internal helper, loads the datamanager for the current contact. Any error triggers a 500.
     *
     * @access private
     */
    private function _load_datamanager()
    {
        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (   ! $this->_datamanager
            || ! $this->_datamanager->autoset_storage($this->_contact))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for contact {$this->_contact->id}.");
            // This will exit.
        }
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
        $this->_controller->set_storage($this->_contact, $this->_schema);
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for contact {$this->_contact->id}.");
            // This will exit.
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
        $tmp = Array();

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "person/{$this->_contact->guid}/",
            MIDCOM_NAV_NAME => $this->_contact->name,
        );

        switch ($handler_id)
        {
            case 'person_edit':
                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => "person/edit/{$this->_contact->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n_midcom->get('edit'),
                );
                break;
            case 'person_delete':
                $tmp[] = Array
                (
                    MIDCOM_NAV_URL => "person/delete/{$this->_contact->guid}/",
                    MIDCOM_NAV_NAME => $this->_l10n_midcom->get('delete'),
                );
                break;
        }

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        $this->_contact = new org_openpsa_contacts_person_dba($args[0]);
        if (! $this->_contact)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The contact {$args[0]} was not found.");
            // This will exit.
        }
        $this->_contact->require_do('midgard:update');

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Reindex the contact
                $indexer = $_MIDCOM->get_service('indexer');
                org_openpsa_contacts_viewer::index_person($this->_controller->datamanager, $indexer, $this->_content_topic);

                // *** FALL-THROUGH ***

            case 'cancel':
                $_MIDCOM->relocate("person/{$this->_contact->guid}/");
                // This will exit.
        }

        org_openpsa_helpers::dm2_savecancel($this);

        $this->_prepare_request_data($handler_id);
        $_MIDCOM->set_pagetitle($this->_contact->name);
        $_MIDCOM->bind_view_to_object($this->_contact, $this->_controller->datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);

        return true;
    }


    /**
     * Shows the loaded contact.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_edit ($handler_id, &$data)
    {
        midcom_show_style('show-person-edit');
    }

    /**
     * Displays a contact delete confirmation view.
     *
     * Note, that the contact for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation contact
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_delete($handler_id, $args, &$data)
    {
        $this->_contact = new org_openpsa_contacts_person_dba($args[0]);

        if (! $this->_contact)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The contact {$args[0]} was not found.");
            // This will exit.
        }
        $this->_contact->require_do('midgard:delete');

        $this->_load_datamanager();

        if (array_key_exists('org_openpsa_contacts_deleteok', $_REQUEST))
        {
            // Deletion confirmed.
            if (! $this->_contact->delete())
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to delete contact {$args[0]}, last Midgard error was: " . midcom_connection::get_error_string());
                // This will exit.
            }

            // Update the index
            $indexer = $_MIDCOM->get_service('indexer');
            $indexer->delete($this->_contact->guid . '_' . $_MIDCOM->i18n->get_content_language());

            // Delete ok, relocating to welcome.
            $_MIDCOM->relocate('');
            // This will exit.
        }

        if (array_key_exists('org_openpsa_contacts_deletecancel', $_REQUEST))
        {
            // Redirect to view page.
            $_MIDCOM->relocate("person/{$this->_contact->guid}/");
            // This will exit()
        }

        $this->_prepare_request_data($handler_id);
        $_MIDCOM->set_pagetitle($this->_contact->name);
        $_MIDCOM->bind_view_to_object($this->_contact, $this->_datamanager->schema->name);
        $this->_update_breadcrumb_line($handler_id);

        return true;
    }


    /**
     * Shows the loaded contact.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_delete ($handler_id, &$data)
    {
        $data['contact_view'] = $this->_datamanager->get_content_html();

        midcom_show_style('show-person-delete');
    }



}

?>