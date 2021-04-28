<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\controller;
use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use midcom\datamanager\storage\blobs;

/**
 * Document handler class.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_document_admin extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_documents_document_dba
     */
    private $_document;

    /**
     * @var controller
     */
    private $_controller;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
    }

    private function load_controller(array $defaults = []) : controller
    {
        return datamanager::from_schemadb($this->_config->get('schemadb_document'))
            ->set_defaults($defaults)
            ->set_storage($this->_document)
            ->get_controller();
    }

    public function _handler_create(Request $request, array &$data)
    {
        $data['directory']->require_do('midgard:create');

        $this->_document = new org_openpsa_documents_document_dba();
        $this->_document->orgOpenpsaAccesstype = org_openpsa_core_acl::ACCESS_WGPRIVATE;

        $defaults = [
            'topic' => $this->_topic->id,
            'author' => midcom_connection::get_user(),
            'orgOpenpsaAccesstype' => $this->_topic->get_parameter('org.openpsa.core', 'orgOpenpsaAccesstype'),
            'orgOpenpsaOwnerWg' => $this->_topic->get_parameter('org.openpsa.core', 'orgOpenpsaOwnerWg'),
        ];
        $this->_controller = $this->load_controller($defaults);

        midcom::get()->head->set_pagetitle($this->_l10n->get('create document'));
        return $this->run_workflow($request);
    }

    private function run_workflow(Request $request) : Response
    {
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->_controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    private function _load_document(string $guid) : org_openpsa_documents_document_dba
    {
        $document = new org_openpsa_documents_document_dba($guid);

        // if the document doesn't belong to the current topic, we don't
        // show it, because otherwise folder-based permissions would be useless
        if ($document->topic != $this->_topic->id) {
            throw new midcom_error_notfound("The document '{$guid}' could not be found in this folder.");
        }

        return $document;
    }

    public function _handler_edit(Request $request, string $guid, array &$data)
    {
        $this->_document = $this->_load_document($guid);
        $this->_document->require_do('midgard:update');

        $this->_controller = $this->load_controller();

        if (   $data['enable_versioning']
            && $request->request->count() > 0) {
            $this->_backup_attachment();
        }
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_document->title));
        return $this->run_workflow($request);
    }

    public function save_callback(controller $controller)
    {
        if (empty($this->_document->title)) {
            $attachments = blobs::get_attachments($this->_document, 'document');
            if (!empty($attachments)) {
                $att = current($attachments);
                $this->_document->title = $att->title;
            }
            $this->_document->update();
        }

        // Update the index
        $indexer = new org_openpsa_documents_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());

        $prefix = '';
        if ($this->_document->topic != $this->_topic->id) {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($this->_document->topic);
            $prefix = $node[MIDCOM_NAV_ABSOLUTEURL];
        }

        return $prefix . "document/" . $this->_document->guid . "/";
    }

    /**
     * Handle versioning of the attachment
     *
     * @todo Move this to the DBA class (using wrapped midcom_db_attachment for change detection)
     */
    private function _backup_attachment()
    {
        if (!empty($_FILES['org_openpsa_documents']['tmp_name']['document'])) {
            $tmp = reset($_FILES['org_openpsa_documents']['tmp_name']['document']);
            if (!empty($tmp['file'])) {
                $this->_document->backup_version();
            }
        }
    }

    public function _handler_delete(Request $request, string $guid)
    {
        $document = $this->_load_document($guid);
        $workflow = $this->get_workflow('delete', ['object' => $document]);
        return $workflow->run($request);
    }
}
