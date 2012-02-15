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
class org_openpsa_documents_handler_document_create extends midcom_baseclasses_components_handler
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

    /**
     * The defaults to use for the new document.
     *
     * @var Array
     */
    private $_defaults = array();

    public function _on_initialize()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_document'));
    }

    /**
     * Internal helper, fires up the creation mode controller. Any error triggers a 500.
     */
    private function _load_create_controller()
    {
        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->schemaname = $this->_schema;
        $this->_controller->callback_object =& $this;
        $this->_controller->defaults =& $this->_defaults;
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
    }

    /**
     * This is what Datamanager calls to actually create a document
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $document = new org_openpsa_documents_document_dba();
        $document->topic = $this->_request_data['directory']->id;
        $document->orgOpenpsaAccesstype = ORG_OPENPSA_ACCESSTYPE_WGPRIVATE;

        if (! $document->create())
        {
            debug_print_r('We operated on this object:', $document);
            throw new midcom_error("Failed to create a new document. Error: " . midcom_connection::get_error_string());
        }

        $this->_document = $document;

        return $document;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $data['directory']->require_do('midgard:create');

        $this->_defaults = array
        (
            'topic' => $this->_request_data['directory']->id,
            'author' => midcom_connection::get_user(),
            'orgOpenpsaAccesstype' => $this->_topic->get_parameter('org.openpsa.core', 'orgOpenpsaAccesstype'),
            'orgOpenpsaOwnerWg' => $this->_topic->get_parameter('org.openpsa.core', 'orgOpenpsaOwnerWg'),
        );

        $this->_load_create_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                /* Index the document */
                $indexer = new org_openpsa_documents_midcom_indexer($this->_topic);
                $indexer->index($this->_controller->datamanager);

                // Relocate to document view
                $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
                if ($this->_document->topic != $this->_topic->id)
                {
                    $nap = new midcom_helper_nav();
                    $node = $nap->get_node($this->_document->topic);
                    $prefix = $node[MIDCOM_NAV_ABSOLUTEURL];
                }

                midcom::get()->relocate($prefix  . "document/" . $this->_document->guid . "/");
                // This will exit
            case 'cancel':
                midcom::get()->relocate('');
                // This will exit
        }
        $this->_request_data['controller'] =& $this->_controller;

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->add_breadcrumb("", $this->_l10n->get('create document'));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        midcom_show_style("show-document-create");
    }
}
?>