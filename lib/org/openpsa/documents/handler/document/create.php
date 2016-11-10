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
 */
class org_openpsa_documents_handler_document_create extends midcom_baseclasses_components_handler
{
    /**
     * The document we're working with (if any).
     *
     * @var org_openpsa_documents_document_dba
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
        $this->_controller->defaults = $this->_defaults;
        if (!$this->_controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 create controller.");
        }
    }

    /**
     * This is what Datamanager calls to actually create a document
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $this->_document = new org_openpsa_documents_document_dba();
        $this->_document->topic = $this->_request_data['directory']->id;
        $this->_document->orgOpenpsaAccesstype = org_openpsa_core_acl::ACCESS_WGPRIVATE;

        if (!$this->_document->create()) {
            debug_print_r('We operated on this object:', $this->_document);
            throw new midcom_error("Failed to create a new document. Error: " . midcom_connection::get_error_string());
        }

        return $this->_document;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $data['directory']->require_do('midgard:create');

        $this->_defaults = array(
            'topic' => $this->_request_data['directory']->id,
            'author' => midcom_connection::get_user(),
            'orgOpenpsaAccesstype' => $this->_topic->get_parameter('org.openpsa.core', 'orgOpenpsaAccesstype'),
            'orgOpenpsaOwnerWg' => $this->_topic->get_parameter('org.openpsa.core', 'orgOpenpsaOwnerWg'),
        );

        $this->_load_create_controller();

        midcom::get()->head->set_pagetitle($this->_l10n->get('create document'));

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $this->_controller,
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        if (empty($this->_document->title)) {
            $attachments = org_openpsa_helpers::get_dm2_attachments($this->_document, 'document');
            if (!empty($attachments)) {
                $att = current($attachments);
                $this->_document->title = $att->title;
            }
            $this->_document->update();
        }

        /* Index the document */
        $indexer = new org_openpsa_documents_midcom_indexer($this->_topic);
        $indexer->index($controller->datamanager);

        // Relocate to document view
        $prefix = '';
        if ($this->_document->topic != $this->_topic->id) {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($this->_document->topic);
            $prefix = $node[MIDCOM_NAV_ABSOLUTEURL];
        }

        return $prefix  . "document/" . $this->_document->guid . "/";
    }
}
