<?php
/**
 * @package org.openpsa.relatedto
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * journal entry handler
 *
 * @package org.openpsa.relatedto
 */
class org_openpsa_relatedto_handler_journal_entry extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * Contains the object the journal_entry is bind to
     */
    private $_current_object = null;

    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('org.openpsa.relatedto');
    }

    public function load_schemadb()
    {
        $schemadb_name = midcom_baseclasses_components_configuration::get('org.openpsa.relatedto', 'config')->get('schemadb_journalentry');
        return midcom_helper_datamanager2_schema::load_database($schemadb_name);
    }

    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_current_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);

        midcom::get()->head->set_pagetitle($this->_l10n->get('add journal entry'));

        $workflow = $this->get_workflow('datamanager2', array('controller' => $this->get_controller('create')));
        return $workflow->run();
    }

    /**
     * Datamanager callback
     */
    public function & dm2_create_callback(&$datamanager)
    {
        $reminder = new org_openpsa_relatedto_journal_entry_dba();
        $reminder->linkGuid = $this->_current_object->guid;
        if (!$reminder->create()) {
            debug_print_r('We operated on this object:', $reminder);
            throw new midcom_error("Failed to create a new reminder. Error: " . midcom_connection::get_error_string());
        }

        return $reminder;
    }

    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $journal_entry = new org_openpsa_relatedto_journal_entry_dba($args[0]);

        $data['controller'] = $this->get_controller('simple', $journal_entry);

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('journal entry')));

        $workflow = $this->get_workflow('datamanager2', array('controller' => $data['controller']));
        if ($journal_entry->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', array('object' => $journal_entry));
            $url_prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";
            $workflow->add_dialog_button($delete, $url_prefix . "delete/" . $journal_entry->guid . "/");
        }
        return $workflow->run();
    }

    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $journal_entry = new org_openpsa_relatedto_journal_entry_dba($args[0]);
        $workflow = $this->get_workflow('delete', array('object' => $journal_entry));
        return $workflow->run();
    }
}
