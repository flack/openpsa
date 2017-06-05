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
class org_openpsa_documents_handler_document_admin extends midcom_baseclasses_components_handler
 implements midcom_helper_datamanager2_interfaces_create
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
     * @var midcom_helper_datamanager2_controller
     */
    private $_controller = null;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
    }

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_document'));
    }

    public function get_schema_defaults()
    {
        return [
            'topic' => $this->_topic->id,
            'author' => midcom_connection::get_user(),
            'orgOpenpsaAccesstype' => $this->_topic->get_parameter('org.openpsa.core', 'orgOpenpsaAccesstype'),
            'orgOpenpsaOwnerWg' => $this->_topic->get_parameter('org.openpsa.core', 'orgOpenpsaOwnerWg'),
        ];
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

        $this->_controller = $this->get_controller('create');

        midcom::get()->head->set_pagetitle($this->_l10n->get('create document'));
        return $this->run_workflow();
    }

    private function run_workflow()
    {
        $workflow = $this->get_workflow('datamanager2', [
            'controller' => $this->_controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    private function _load_document($guid)
    {
        $document = new org_openpsa_documents_document_dba($guid);

        // if the document doesn't belong to the current topic, we don't
        // show it, because otherwise folder-based permissions would be useless
        if ($document->topic != $this->_topic->id) {
            throw new midcom_error_notfound("The document '{$guid}' could not be found in this folder.");
        }

        return $document;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_document = $this->_load_document($args[0]);
        $this->_document->require_do('midgard:update');

        $this->_controller = $this->get_controller('simple', $this->_document);

        if (   $data['enable_versioning']
            && !empty($_POST)) {
            $this->_backup_attachment();
        }
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_document->title));
        return $this->run_workflow();
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

        // Update the index
        $indexer = new org_openpsa_documents_midcom_indexer($this->_topic);
        $indexer->index($this->_controller->datamanager);

        $prefix = '';
        if ($this->_document->topic != $this->_topic->id) {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($this->_document->topic);
            $prefix = $node[MIDCOM_NAV_ABSOLUTEURL];
        }

        return $prefix  . "document/" . $this->_document->guid . "/";
    }

    /**
     * Handle versioning of the attachment
     *
     * @todo Move this to the DBA wrapper class when DM datatype_blob behaves better
     */
    private function _backup_attachment()
    {
        // First, look at post data (from in-form replace/delete buttons)
        if (!empty($_POST['document'])) {
            foreach (array_keys($_POST['document']) as $key) {
                if (    strpos($key, '_delete')
                    || (    strpos($key, '_upload')
                        && !strpos($key, 'new_upload'))) {
                    $this->_document->backup_version();
                    return;
                }
            }
        }

        // If nothing is found, try looking in quickform (regular form submission)
        $group = $this->_controller->formmanager->form->getElement('document');
        foreach ($group->getElements() as $element) {
            if (   preg_match('/e_exist_.+?_file$/', $element->getName())
                && $element->isUploadedFile()) {
                $this->_document->backup_version();
                return;
            }
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_document = $this->_load_document($args[0]);
        $workflow = $this->get_workflow('delete', ['object' => $this->_document]);
        return $workflow->run();
    }
}
