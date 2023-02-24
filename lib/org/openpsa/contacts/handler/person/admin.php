<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contacts edit/delete person handler
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_admin extends midcom_baseclasses_components_handler
{
    use org_openpsa_contacts_handler;

    private org_openpsa_contacts_person_dba $_contact;

    private function load_controller() : controller
    {
        $schema = $this->get_person_schema($this->_contact);
        return datamanager::from_schemadb($this->_config->get('schemadb_person'))
            ->set_storage($this->_contact, $schema)
            ->get_controller();
    }

    /**
     * Displays a contact edit view.
     */
    public function _handler_edit(Request $request, string $guid)
    {
        $this->_contact = new org_openpsa_contacts_person_dba($guid);
        $this->_contact->require_do('midgard:update');

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('person')));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        $indexer = new org_openpsa_contacts_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());
        return $this->router->generate('person_view', ['guid' => $this->_contact->guid]);
    }

    public function _handler_delete(Request $request, string $guid)
    {
        $contact = new org_openpsa_contacts_person_dba($guid);
        $workflow = $this->get_workflow('delete', ['object' => $contact]);
        return $workflow->run($request);
    }
}
