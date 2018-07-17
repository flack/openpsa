<?php
/**
 * @package org.openpsa.contacts
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * Contacts edit/delete person handler
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_admin extends midcom_baseclasses_components_handler
{
    use org_openpsa_contacts_handler;

    /**
     * The contact to operate on
     *
     * @var org_openpsa_contacts_person_dba
     */
    private $_contact;

    /**
     * @return \midcom\datamanager\controller
     */
    private function load_controller()
    {
        $schema = $this->get_person_schema($this->_contact);
        return datamanager::from_schemadb($this->_config->get('schemadb_person'))
            ->set_storage($this->_contact, $schema)
            ->get_controller();
    }

    /**
     * Displays a contact edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_contact = new org_openpsa_contacts_person_dba($args[0]);
        $this->_contact->require_do('midgard:update');

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('person')));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(controller $controller)
    {
        $indexer = new org_openpsa_contacts_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());
        return $this->router->generate('person_view', ['guid' => $this->_contact->guid]);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_contact = new org_openpsa_contacts_person_dba($args[0]);
        $workflow = $this->get_workflow('delete', ['object' => $this->_contact]);
        return $workflow->run();
    }
}
