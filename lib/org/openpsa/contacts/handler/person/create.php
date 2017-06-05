<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts person handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The person we're working on, if any
     *
     * @param org_openpsa_contacts_person_dba
     */
    private $_person = null;

    /**
     * The parent group, if any
     *
     * @var org_openpsa_contacts_group_dba
     */
    private $_group = null;

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
    }

    public function get_schema_defaults()
    {
        $defaults = [];
        if ($this->_group) {
            if ($this->_group->orgOpenpsaObtype >= org_openpsa_contacts_group_dba::ORGANIZATION) {
                $defaults['organizations'] = [$this->_group->id];
            } elseif ($this->_group->orgOpenpsaObtype < org_openpsa_contacts_group_dba::MYCONTACTS) {
                $defaults['groups'] = [$this->_group->id];
            }
        }
        return $defaults;
    }

    /**
     * This is what Datamanager calls to actually create a person
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $this->_person = new org_openpsa_contacts_person_dba();

        if (!$this->_person->create()) {
            debug_print_r('We operated on this object:', $this->_person);
            throw new midcom_error("Failed to create a new person, cannot continue. Error: " . midcom_connection::get_error_string());
        }

        return $this->_person;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba');

        if (count($args) > 0) {
            // Get the organization
            $this->_group = new org_openpsa_contacts_group_dba($args[0]);
            $this->_group->require_do('midgard:create');
        }

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('person')));

        $workflow = $this->get_workflow('datamanager2', [
            'controller' => $this->get_controller('create'),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        // Index the person
        $indexer = new org_openpsa_contacts_midcom_indexer($this->_topic);
        $indexer->index($controller->datamanager);
        return "person/{$this->_person->guid}/";
    }
}
