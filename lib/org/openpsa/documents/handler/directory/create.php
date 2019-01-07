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

/**
 * Directory create handler.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_directory_create extends midcom_baseclasses_components_handler
{
    private function load_controller()
    {
        $topic = new org_openpsa_documents_directory();
        $topic->up = $this->_request_data['directory']->id;
        $topic->component = $this->_component;

        return datamanager::from_schemadb($this->_config->get('schemadb_directory'))
            ->set_storage($topic)
            ->get_controller();
    }

    /**
     * @param array &$data The local request data.
     */
    public function _handler_create(Request $request, array &$data)
    {
        $data['directory']->require_do('midgard:create');

        midcom::get()->head->set_pagetitle($this->_l10n->get('new directory'));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        $indexer = new org_openpsa_documents_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());

        return $controller->get_datamanager()->get_storage()->get_value()->name. "/";
    }
}
