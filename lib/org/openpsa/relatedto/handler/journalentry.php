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
class org_openpsa_relatedto_handler_journalentry extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * Contains the one journal_entry to edit
     */
    private $_journal_entry = null;

    /**
     * Contains the query-builder for journal-entries
     */
    private $qb_journal_entries = null;

    /**
     * Contains the object the journal_entry is bind to
     */
    private $_current_object = null;

    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('org.openpsa.relatedto');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_entry($handler_id, array $args, array &$data)
    {
        $this->_current_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);

        $this->_relocate_url = midcom::get()->permalinks->create_permalink($this->_current_object->guid);
        $data['url_prefix'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";

         //add needed constraints etc. to the query-builder
        $this->qb_journal_entries = org_openpsa_relatedto_journal_entry_dba::new_query_builder();
        $this->qb_journal_entries->add_constraint('linkGuid', '=', $args[0]);
        $this->qb_journal_entries->add_order('followUp', 'DESC');
        $this->_prepare_journal_query();

        $data['entries'] = $this->qb_journal_entries->execute();
        $data['object'] = $this->_current_object;
        $data['page'] = 1;

        //because we only show entries of one object there is no need to show the object for every entry
        $data['show_closed'] = true;
        $data['show_object'] = false;

        $this->_prepare_output();
        org_openpsa_widgets_grid::add_head_elements();

        //prepare breadcrumb
        if ($object_url = midcom::get()->permalinks->create_permalink($this->_current_object->guid)) {
            $ref = midcom_helper_reflector::get($this->_current_object);
            $this->add_breadcrumb($object_url, $ref->get_object_label($this->_current_object));
        }
        $this->add_breadcrumb(
            midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/render/" . $this->_current_object->guid . "/both/",
            $this->_l10n->get('view related information')
        );
        $this->add_breadcrumb("", $this->_l10n->get('journal entries'));
    }

    /**
     * function to add css & toolbar-items
     */
    private function _prepare_output()
    {
        $buttons = array();
        $buttons[] = array(
            MIDCOM_TOOLBAR_URL => $this->_relocate_url,
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('back'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
        );
        $workflow = $this->get_workflow('datamanager2');
        $buttons[] = $workflow->get_button($this->_request_data['url_prefix'] . "create/" . $this->_current_object->guid . "/", array(
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('add journal entry'),
            MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png'
        ));
        $this->_view_toolbar->add_items($buttons);

        org_openpsa_widgets_contact::add_head_elements();
    }

    public function _show_entry($handler_id, &$data)
    {
        midcom_show_style('show_entries_html');
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

    public function _handler_remove($handler_id, array $args, array &$data)
    {
        $this->_current_object = midcom::get()->dbfactory->get_object_by_guid($args[0]);
        $reminder = new org_openpsa_relatedto_journal_entry_dba($args[1]);

        $reminder->delete();

        $add_url = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/reminder/";
        $add_url = $add_url . $this->_current_object->guid . "/";

        return new midcom_response_relocate($add_url);
    }

    public function load_schemadb()
    {
        $schemadb_name = midcom_baseclasses_components_configuration::get('org.openpsa.relatedto', 'config')->get('schemadb_journalentry');
        return midcom_helper_datamanager2_schema::load_database($schemadb_name);
    }

    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_journal_entry = new org_openpsa_relatedto_journal_entry_dba($args[0]);
        $this->_current_object = midcom::get()->dbfactory->get_object_by_guid($this->_journal_entry->linkGuid);

        $data['controller'] = $this->get_controller('simple', $this->_journal_entry);

        $url_prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('journal entry')));

        $workflow = $this->get_workflow('datamanager2', array('controller' => $data['controller']));
        if ($this->_journal_entry->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', array('object' => $this->_journal_entry));
            $workflow->add_dialog_button($delete, $url_prefix . "delete/" . $this->_journal_entry->guid . "/");
        }
        return $workflow->run();
    }

    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $journal_entry = new org_openpsa_relatedto_journal_entry_dba($args[0]);

        if (!$journal_entry->delete()) {
            throw new midcom_error("Failed to delete journal_entry: " . $args[0] . " Last Error was :" . midcom_connection::get_error_string());
        }

        $object = midcom::get()->dbfactory->get_object_by_guid($journal_entry->linkGuid);
        return new midcom_response_relocate("__mfa/org.openpsa.relatedto/journalentry/" . $object->guid . '/');
    }

    public function _handler_list($handler_id, $args, &$data)
    {
        $this->qb_journal_entries = org_openpsa_relatedto_journal_entry_dba::new_query_builder();
        $this->qb_journal_entries->add_order('followUp');
        $this->_prepare_journal_query();

        //show the corresponding object of the entry
        $data['show_object'] = true;
        $data['show_closed'] = array_key_exists('show_closed', $_POST);
        $data['page'] = 1;

        $data['entries'] = $this->qb_journal_entries->execute();

        //get the corresponding objects
        if (!empty($data['entries'])) {
            $data['linked_objects'] = array();
            $data['linked_raw_objects'] = array();

            foreach ($data['entries'] as $i => $entry) {
                if (array_key_exists($entry->linkGuid, $data['linked_objects'])) {
                    continue;
                }
                //create reflector with linked object to get the right label
                try {
                    $linked_object = midcom::get()->dbfactory->get_object_by_guid($entry->linkGuid);
                } catch (midcom_error $e) {
                    unset($data['entries'][$i]);
                    $e->log();
                    continue;
                }

                $reflector = midcom_helper_reflector::get($linked_object);
                $link_html = "<a href='" . midcom::get()->permalinks->create_permalink($linked_object->guid) . "'>" . $reflector->get_object_label($linked_object) ."</a>";
                $data['linked_objects'][$entry->linkGuid] = $link_html;
                $data['linked_raw_objects'][$entry->linkGuid] = $reflector->get_object_label($linked_object);
            }
        }
        //url_prefix to build the links to the entries
        $data['url_prefix'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "__mfa/org.openpsa.relatedto/journalentry/";
        midcom::get()->header("Content-type: text/xml; charset=UTF-8");
        midcom::get()->skip_page_style = true;
    }

    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('show_entries_xml');
    }

    private function _prepare_journal_query()
    {
        if (array_key_exists('journal_entry_constraints', $_POST)) {
            foreach ($_POST['journal_entry_constraints'] as $constraint) {
                //"type-cast" for closed because it will be passed as string
                if ($constraint['property'] == 'closed') {
                    $constraint['value'] = ($constraint['value'] != 'false');
                }
                $this->qb_journal_entries->add_constraint($constraint['property'], $constraint['operator'], $constraint['value']);
            }
        }
        //check if there is a page & rows - parameter passed - if add them to qb
        if (array_key_exists('page', $_POST) && array_key_exists('rows', $_POST)) {
            $this->_request_data['page'] = $_POST['page'];
            $this->qb_journal_entries->set_limit((int)$_POST['rows']);
            $offset = ((int)$_POST['page'] - 1) * (int)$_POST['rows'];
            $this->qb_journal_entries->set_offset($offset);
        }
    }
}
