<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents document handler and viewer class.
 *
 * @package org.openpsa.documents
 *
 */
class org_openpsa_documents_handler_document_admin extends midcom_baseclasses_components_handler
{
    /**
     * The document we're working with (if any).
     *
     * @var org_openpsa_documents_documen_dba
     */
    private $_document = null;

    /**
     * The Controller of the document used for creating or editing
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
     * The schema to use for the new document.
     *
     * @var string
     */
    private $_schema = 'default';

    private $_datamanager = null;

    public function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_document'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
    }

    /**
     * Internal helper, loads the controller for the current document. Any error triggers a 500.
     */
    private function _load_edit_controller()
    {
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_document, $this->_schema);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error( "Failed to initialize a DM2 controller instance for document {$this->_document->id}.");
        }
    }

    private function _load_document($guid)
    {
        $document = new org_openpsa_documents_document_dba($guid);

        // if the document doesn't belong to the current topic, we don't
        // show it, because otherwise folder-based permissions would be useless
        if ($document->topic != $this->_topic->id)
        {
            throw new midcom_error_notfound("The document '{$guid}' could not be found in this folder.");
        }

        // Load the document to datamanager
        if (!$this->_datamanager->autoset_storage($document))
        {
            debug_print_r('DM instance was:', $this->_datamanager);
            debug_print_r('Object to be used was:', $document);
            throw new midcom_error('Failed to initialize the datamanager, see debug level log for more information.');
        }

        return $document;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_edit($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_document = $this->_load_document($args[0]);
        $this->_document->require_do('midgard:update');

        $this->_load_edit_controller();

        if (   $data['enable_versioning']
            && !empty($_POST))
        {
            $this->_backup_attachment();
        }

        switch ($this->_controller->process_form())
        {
            case 'save':
                // TODO: Update the URL name?

                // Update the Index
                $indexer = $_MIDCOM->get_service('indexer');
                $indexer->index($this->_controller->datamanager);

                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                if ($this->_document->topic != $this->_topic->id)
                {
                    $nap = new midcom_helper_nav();
                    $node = $nap->get_node($this->_document->topic);
                    $prefix = $node[MIDCOM_NAV_ABSOLUTEURL];
                }

                $_MIDCOM->relocate($prefix  . "document/" . $this->_document->guid . "/");
                // This will exit()

            case 'cancel':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                                   . "document/" . $this->_document->guid . "/");
                // This will exit()
        }

        $this->_request_data['controller'] =& $this->_controller;

        $_MIDCOM->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_document->title));

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);
        $_MIDCOM->bind_view_to_object($this->_document, $this->_controller->datamanager->schema->name);

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.core/ui-elements.css');

        $this->_update_breadcrumb_line('edit');

        return true;
    }

    /**
     * Handle versioning of the attachment
     *
     * @todo Move this to the DBA wrapper class when DM datatype_blob behaves better
     */
    private function _backup_attachment()
    {
        // First, look at post data (from in-form replace/delete buttons)
        if (array_key_exists('document', $_POST))
        {
            if (sizeof($_POST['document']) > 0)
            {
                foreach ($_POST['document'] as $key => $value)
                {
                    if (    strpos($key, '_delete')
                        || (strpos($key, '_upload')
                            && !strpos($key, 'new_upload')))
                    {
                        $this->_document->backup_version();
                        return;
                    }
                }
            }
        }

        // If nothing is found, try looking in quickform (regular form submission)
        $group = $this->_controller->formmanager->form->getElement('document');
        foreach ($group->getElements() as $element)
        {
            if (   preg_match('/e_exist_.+?_file$/', $element->getName())
                && $element->isUploadedFile())
            {
                $this->_document->backup_version();
                return;
            }
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_edit($handler_id, &$data)
    {
        midcom_show_style("show-document-edit");
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_delete($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_document = $this->_load_document($args[0]);

        $_MIDCOM->auth->require_do('midgard:delete', $this->_document);

        $delete_succeeded = false;
        if (array_key_exists('org_openpsa_documents_deleteok', $_POST))
        {
            $delete_succeeded = $this->_document->delete();
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            if ($delete_succeeded)
            {
                // Update the index
                $indexer = $_MIDCOM->get_service('indexer');
                $indexer->delete($this->_document->guid);
                // Redirect to the directory
                $_MIDCOM->relocate($prefix);
                // This will exit
            }
            else
            {
                // Failure, give a message
                $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.documents'), $this->_l10n->get("failed to delete document, reason ").midcom_connection::get_error_string(), 'error');
                $_MIDCOM->relocate($prefix . '/document/' . $this->_document->guid . '/');
                // This will exit
            }
        }
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'javascript:document.getElementById("org_openpsa_documents_document_deleteform").submit();',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get("delete"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_OPTIONS  => array
                (
                    'rel' => 'directlink',
                ),
             )
         );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'document/' . $this->_document->guid.'/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get("cancel"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/cancel.png',
             )
         );
        $this->_update_breadcrumb_line('delete');

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_delete($handler_id, &$data)
    {
        $this->_request_data['document_dm'] =& $this->_datamanager;
        $this->_request_data['document'] =& $this->_document;
        midcom_show_style("show-document-delete");
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     * @param mixed $action The current action
     */
    private function _update_breadcrumb_line($action = false)
    {
        $this->add_breadcrumb("document/{$this->_document->guid}/", $this->_document->title);

        if ($action)
        {
            $this->add_breadcrumb("", sprintf($this->_l10n_midcom->get($action . ' %s'), $this->_l10n->get('document')));
        }
    }
}
?>