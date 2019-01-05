<?php
/**
 * @package org.openpsa.relatedto
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * journal entry handler
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_handler_journal_entry extends midcom_baseclasses_components_handler
{
    /**
     * Contains the object the journal_entry is bind to
     */
    private $_current_object = null;

    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('org.openpsa.relatedto');
    }

    /**
     * @return \midcom\datamanager\controller
     */
    private function load_controller(org_openpsa_relatedto_journal_entry_dba $entry)
    {
        $schemadb_name = midcom_baseclasses_components_configuration::get('org.openpsa.relatedto', 'config')->get('schemadb_journalentry');
        return datamanager::from_schemadb($schemadb_name)
            ->set_storage($entry)
            ->get_controller();
    }

    public function _handler_create(array $args)
    {
        $this->_current_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);
        $entry= new org_openpsa_relatedto_journal_entry_dba();
        $entry->linkGuid = $this->_current_object->guid;

        midcom::get()->head->set_pagetitle($this->_l10n->get('add journal entry'));

        $workflow = $this->get_workflow('datamanager', ['controller' => $this->load_controller($entry)]);
        return $workflow->run();
    }

    public function _handler_edit(array $args, array &$data)
    {
        $entry = new org_openpsa_relatedto_journal_entry_dba($args[0]);
        $data['controller'] = $this->load_controller($entry);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('journal entry')));

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);
        if ($entry->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', ['object' => $entry]);
            $url = $this->router->generate('journal_entry_delete', ['guid' => $entry->guid ]);
            $workflow->add_dialog_button($delete, $url);
        }
        return $workflow->run();
    }

    public function _handler_delete(array $args)
    {
        $journal_entry = new org_openpsa_relatedto_journal_entry_dba($args[0]);
        $workflow = $this->get_workflow('delete', ['object' => $journal_entry]);
        return $workflow->run();
    }
}
