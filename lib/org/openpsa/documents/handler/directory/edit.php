<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * org.openpsa.documents document handler and viewer class.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_directory_edit extends midcom_baseclasses_components_handler
{
    public function _handler_edit(Request $request, array &$data)
    {
        $data['directory']->require_do('midgard:update');

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('directory')));

        $controller = datamanager::from_schemadb($this->_config->get('schemadb_directory'))
            ->set_storage($this->_request_data['directory'], 'default')
            ->get_controller();

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        $indexer = new org_openpsa_documents_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());

        return '';
    }
}
