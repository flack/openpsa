<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: edit.php 25319 2010-03-18 12:44:12Z indeyets $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents document handler and viewer class.
 *
 * @package org.openpsa.documents
 *
 */
class org_openpsa_documents_handler_directory_edit extends midcom_baseclasses_components_handler
{
    /**
     * The Controller of the directory used for creating or editing
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
     * The schema to use for the new directory.
     *
     * @var string
     */
    private $_schema = 'default';

    public function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
    }

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_directory'));
    }

    /**
     * Internal helper, loads the controller for the current directoy. Any error triggers a 500.
     */
    private function _load_edit_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_request_data['directory'], $this->_schema);
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for task {$this->_directory->id}.");
            // This will exit.
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_edit($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_do('midgard:update', $this->_request_data['directory']);

        $this->_load_edit_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // TODO: Update the URL name?

                // Update the Index
                $indexer = $_MIDCOM->get_service('indexer');
                $indexer->index($this->_controller->datamanager);

                $this->_view = "default";
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX));
                // This will exit()

            case 'cancel':
                $this->_view = "default";
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX));
                // This will exit()
        }

        $this->_request_data['controller'] = $this->_controller;

        $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('directory')));

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);
        $_MIDCOM->bind_view_to_object($this->_request_data['directory'], $this->_controller->datamanager->schema->name);

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_edit($handler_id, &$data)
    {
        midcom_show_style("show-directory-edit");
    }
}
?>