<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * org.openpsa.contacts person create handler.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_create extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_contacts_person_dba
     */
    private $_person;

    public function _handler_create(Request $request, string $guid = null)
    {
        midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_contacts_person_dba::class);

        $defaults = [];
        if (!empty($guid)) {
            // Get the organization
            $group = new org_openpsa_contacts_group_dba($guid);
            $group->require_do('midgard:create');

            if ($group->orgOpenpsaObtype >= org_openpsa_contacts_group_dba::ORGANIZATION) {
                $defaults['organizations'] = [$group->id];
            } elseif ($group->orgOpenpsaObtype < org_openpsa_contacts_group_dba::MYCONTACTS) {
                $defaults['groups'] = [$group->id];
            }
        }

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('person')));
        $this->_person = new org_openpsa_contacts_person_dba();

        $dm = datamanager::from_schemadb($this->_config->get('schemadb_person'))
            ->set_defaults($defaults)
            ->set_storage($this->_person);
        $workflow = $this->get_workflow('datamanager', [
            'controller' => $dm->get_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        // Index the person
        $indexer = new org_openpsa_contacts_midcom_indexer($this->_topic);
        $indexer->index($controller->get_datamanager());
        return $this->router->generate('person_view', ['guid' => $this->_person->guid]);
    }
}
